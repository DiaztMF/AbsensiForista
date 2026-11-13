import { Head, Link, router, usePage } from "@inertiajs/react";
import { Bell, Calendar, Clock, Hand, LayoutGrid, LogOut, MapPin, ShieldCheck } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import type { Auth } from "@/types/auth";

type PageProps = {
    auth: Auth;
};
import AgendaLocationMap from "@/components/Member/AgendaLocationMap";
import CheckInTrigger from "@/components/Member/CheckInTrigger";
import DigitalTimeBlocks from "@/components/Member/DigitalTimeBlocks";
import FeedbackModal from "@/components/Member/FeedbackModal";
import NotificationDrawer from "@/components/Member/NotificationDrawer";

interface ActiveSession {
    id: number;
    title: string;
    location_name: string;
    address: string | null;
    latitude: number;
    longitude: number;
    radius_m: number;
    session_date: string;
    starts_at: string;
    ends_at: string;
    window_opens_at: string;
    window_closes_at: string;
    has_checked_in: boolean;
    checked_at: string | null;
    distance_m: number | null;
    source: "auto" | "manual" | null;
    failed_attempts: number;
    remaining_attempts: number;
    is_locked: boolean;
}

interface MemberStats {
    total_sessions: number;
    attended_count: number;
    percentage: number;
    score: number;
}

interface HistoryItem {
    id: number;
    agenda_title: string;
    location_name: string;
    session_date: string;
    month_key: string;
    month_label: string;
    checked_at: string;
    distance_m: number | null;
    source: "auto" | "manual";
}

interface DashboardProps {
    serverNow: string;
    activeSession: ActiveSession | null;
    stats: MemberStats;
    history: HistoryItem[];
    unreadNotificationsCount: number;
    vapidPublicKey: string | null;
}

interface MemberFlash {
    checkin_result?: {
        success: boolean;
        message: string;
    };
    show_welcome_popup?: boolean;
}

const clock = (iso: string) =>
    new Date(iso)
        .toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" })
        .replace(".", ":");

export default function MemberDashboard({
    serverNow,
    activeSession,
    stats,
    history,
    unreadNotificationsCount,
    vapidPublicKey,
}: DashboardProps) {
    const page = usePage<PageProps>();
    const user = page.props.auth.user;
    const flash = page.flash as MemberFlash;

    const [bottomTab, setBottomTab] = useState<"dashboard" | "attendance">("dashboard");
    const [attendanceSubTab, setAttendanceSubTab] = useState<"today" | "list">("today");
    const [selectedMonth, setSelectedMonth] = useState("all");
    const [isLocating, setIsLocating] = useState(false);
    const [notifOpen, setNotifOpen] = useState(false);
    const [unreadCount, setUnreadCount] = useState(unreadNotificationsCount);

    const [welcomeOpen, setWelcomeOpen] = useState(Boolean(flash.show_welcome_popup));
    const [feedbackModal, setFeedbackModal] = useState<{
        open: boolean;
        variant: "success" | "error";
        title: string;
        message: string;
    }>({ open: false, variant: "success", title: "", message: "" });

    // Jam mengikuti waktu server; jam HP tidak berpengaruh (F4 PRD)
    const [nowMs, setNowMs] = useState(() => new Date(serverNow).getTime());
    useEffect(() => {
        setNowMs(new Date(serverNow).getTime());
        const interval = setInterval(() => setNowMs((v) => v + 1000), 1000);

        return () => clearInterval(interval);
    }, [serverNow]);

    useEffect(() => {
        const result = flash.checkin_result;
        if (!result) {
            return;
        }

        setFeedbackModal({
            open: true,
            variant: result.success ? "success" : "error",
            title: result.success ? "Presensi berhasil!" : "Presensi gagal",
            message: result.message,
        });

        // F5 PRD: perbarui dashboard tanpa reload penuh
        if (result.success) {
            router.reload({ only: ["activeSession", "stats", "history"] });
        }
    }, [flash.checkin_result]);

    const windowState = useMemo(() => {
        if (!activeSession) {
            return { isOpen: false, label: "Tidak ada sesi" };
        }

        if (nowMs < new Date(activeSession.window_opens_at).getTime()) {
            return { isOpen: false, label: `Dibuka ${clock(activeSession.window_opens_at)}` };
        }

        if (nowMs > new Date(activeSession.window_closes_at).getTime()) {
            return { isOpen: false, label: "Closed" };
        }

        return { isOpen: true, label: "Window Terbuka" };
    }, [activeSession, nowMs]);

    const availableMonths = useMemo(
        () => Array.from(new Map(history.map((h) => [h.month_key, h.month_label]))),
        [history],
    );

    const filteredHistory = useMemo(
        () =>
            selectedMonth === "all"
                ? history
                : history.filter((h) => h.month_key === selectedMonth),
        [history, selectedMonth],
    );

    const handleCheckIn = () => {
        if (!activeSession || isLocating) {
            return;
        }

        if (!("geolocation" in navigator)) {
            setFeedbackModal({
                open: true,
                variant: "error",
                title: "GPS tidak didukung",
                message: "Perangkat atau browser Anda tidak mendukung pelacakan lokasi GPS.",
            });

            return;
        }

        setIsLocating(true);
        navigator.geolocation.getCurrentPosition(
            (position) => {
                router.post(
                    `/sessions/${activeSession.id}/check-in`,
                    {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy_m: position.coords.accuracy,
                    },
                    {
                        preserveScroll: true,
                        onFinish: () => setIsLocating(false),
                    },
                );
            },
            (error) => {
                setIsLocating(false);
                setFeedbackModal({
                    open: true,
                    variant: "error",
                    title: "Akses lokasi bermasalah",
                    message:
                        error.code === error.PERMISSION_DENIED
                            ? "Izin lokasi ditolak. Buka Pengaturan Browser > Izin Situs > Lokasi, lalu izinkan akses GPS untuk melanjutkan presensi."
                            : "Gagal mendapatkan titik GPS yang akurat. Pastikan GPS aktif dan Anda berada di area terbuka.",
                });
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
        );
    };

    const openAttendance = (subTab: "today" | "list") => {
        setBottomTab("attendance");
        setAttendanceSubTab(subTab);
    };

    const absentCount = Math.max(0, stats.total_sessions - stats.attended_count);
    const windowOpenFormatted = activeSession ? clock(activeSession.window_opens_at) : "00:00";

    return (
        <div className="flex min-h-dvh justify-center bg-[#F6F1EB]">
            <Head title="Dashboard Anggota — FORISTA" />

            <div className="relative flex min-h-dvh w-full max-w-md flex-col bg-[#F6F1EB] pb-28">
                {bottomTab === "dashboard" ? (
                    <>
                        <header className="rounded-b-[36px] bg-[#5C3806] px-5 pt-8 pb-24 text-white shadow-md">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-full border-2 border-white/30 bg-[#F39C12] text-base font-bold">
                                        {user.name.slice(0, 2).toUpperCase()}
                                    </div>
                                    <div>
                                        <h1 className="text-base leading-tight font-bold">
                                            {user.name}
                                        </h1>
                                        <p className="text-xs text-white/75">
                                            {user.division?.name ?? "Anggota FORISTA"}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-center gap-2">
                                    {user.role === "admin" && (
                                        <Link
                                            href="/admin"
                                            className="flex items-center gap-1.5 rounded-xl bg-white/15 px-3 py-2 text-xs font-semibold hover:bg-white/25"
                                        >
                                            <ShieldCheck className="h-4 w-4" />
                                            Admin
                                        </Link>
                                    )}
                                    <button
                                        type="button"
                                        onClick={() => setNotifOpen(true)}
                                        className="relative flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 hover:bg-white/25"
                                        aria-label="Buka notifikasi"
                                    >
                                        <Bell className="h-5 w-5" />
                                        {unreadCount > 0 && (
                                            <span className="absolute top-2 right-2 h-2.5 w-2.5 rounded-full bg-[#EF4444] ring-2 ring-[#5C3806]" />
                                        )}
                                    </button>
                                </div>
                            </div>

                            <div className="mt-7 flex items-center justify-between">
                                <h2 className="text-base font-bold">Presensi Hari Ini</h2>
                                <span className="text-xs text-white/80">
                                    {new Date(nowMs).toLocaleDateString("id-ID", {
                                        day: "numeric",
                                        month: "short",
                                        year: "numeric",
                                    })}
                                </span>
                            </div>
                        </header>

                        <section className="-mt-16 px-5">
                            <div className="rounded-3xl border border-[#EFEAE4] bg-white p-5 text-center shadow-sm">
                                {activeSession ? (
                                    <>
                                        <DigitalTimeBlocks
                                            label={activeSession.title}
                                            timeString={windowOpenFormatted}
                                        />
                                        <p className="mt-3 flex items-center justify-center gap-1.5 text-xs text-[#7C7269]">
                                            <MapPin className="h-3.5 w-3.5 shrink-0 text-[#5C3806]" />
                                            <span className="truncate">
                                                {activeSession.location_name}
                                            </span>
                                        </p>

                                        <button
                                            type="button"
                                            onClick={() => openAttendance("today")}
                                            className="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#F39C12] px-6 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-[#DF8C09]"
                                        >
                                            <Clock className="h-4 w-4" />
                                            {activeSession.has_checked_in
                                                ? "Lihat Detail Kehadiran"
                                                : "Buka Layar Check-In"}
                                        </button>
                                    </>
                                ) : (
                                    <div className="py-6">
                                        <p className="text-sm font-bold text-[#1E1915]">
                                            Belum ada rapat terjadwal
                                        </p>
                                        <p className="mt-1 text-xs text-[#7C7269]">
                                            Jadwal pertemuan mingguan berikutnya akan tampil
                                            otomatis di sini.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </section>

                        <section className="mt-6 px-5">
                            <div className="mb-3 flex items-center justify-between">
                                <h3 className="text-sm font-bold text-[#1E1915]">
                                    Total Kehadiran{" "}
                                    <span className="font-normal text-[#7C7269]">
                                        (Periode Ini)
                                    </span>
                                </h3>
                                <span className="rounded-lg border border-[#EAE4DC] bg-white px-3 py-1 text-xs font-semibold">
                                    {stats.total_sessions} Sesi
                                </span>
                            </div>

                            <div className="grid grid-cols-3 divide-x divide-[#F1ECE6] rounded-3xl border border-[#EFEAE4] bg-white p-4 shadow-xs">
                                <div className="flex flex-col items-center px-2">
                                    <div className="mb-1.5 flex h-11 w-11 items-center justify-center rounded-xl bg-[#E8F8F0] text-base font-bold text-[#0FA958]">
                                        {stats.attended_count}
                                    </div>
                                    <span className="text-xs font-medium text-[#1E1915]">
                                        Hadir
                                    </span>
                                </div>

                                <div className="flex flex-col items-center px-2">
                                    <div className="mb-1.5 flex h-11 w-11 items-center justify-center rounded-xl bg-[#FFF4E5] text-sm font-bold text-[#F39C12]">
                                        {stats.percentage}%
                                    </div>
                                    <span className="text-xs font-medium text-[#1E1915]">
                                        Persentase
                                    </span>
                                </div>

                                <div className="flex flex-col items-center px-2">
                                    <div className="mb-1.5 flex h-11 w-11 items-center justify-center rounded-xl bg-[#EEF0FF] text-base font-bold text-[#5B4DFF]">
                                        {stats.score.toFixed(1)}
                                    </div>
                                    <span className="text-xs font-medium text-[#1E1915]">
                                        Skor (1-10)
                                    </span>
                                </div>
                            </div>
                        </section>

                        <section className="mt-6 px-5">
                            <div className="mb-3 flex items-center justify-between">
                                <h3 className="text-sm font-bold text-[#1E1915]">
                                    Ringkasan Partisipasi
                                </h3>
                                <button
                                    type="button"
                                    onClick={() => openAttendance("list")}
                                    className="text-xs font-semibold text-[#F39C12]"
                                >
                                    Semua Riwayat →
                                </button>
                            </div>

                            <div className="space-y-3 rounded-3xl border border-[#EFEAE4] bg-white p-4 shadow-xs">
                                <div className="flex items-center justify-between text-xs">
                                    <span className="text-[#7C7269]">Total Pertemuan Diikuti</span>
                                    <span className="font-bold text-[#1E1915]">
                                        {stats.attended_count} dari {stats.total_sessions} Rapat
                                    </span>
                                </div>
                                <div className="flex items-center justify-between text-xs">
                                    <span className="text-[#7C7269]">
                                        Tidak Hadir / Belum Tercatat
                                    </span>
                                    <span className="font-bold text-[#EF4444]">
                                        {absentCount} Rapat
                                    </span>
                                </div>
                                <div className="h-2.5 w-full overflow-hidden rounded-full bg-[#F4F3F8]">
                                    <div
                                        className="h-full rounded-full bg-[#5B4DFF] transition-all duration-500"
                                        style={{
                                            width: `${Math.min(100, stats.percentage)}%`,
                                        }}
                                    />
                                </div>
                            </div>
                        </section>
                    </>
                ) : (
                    <div className="px-5 pt-7">
                        <h1 className="mb-5 text-center text-base font-bold text-[#1E1915]">
                            Presensi Anggota
                        </h1>

                        <div className="mb-5 grid grid-cols-2 gap-1.5 rounded-2xl border border-[#EAE4DC] bg-white p-1.5">
                            {(
                                [
                                    ["today", "Presensi Hari Ini"],
                                    ["list", "Riwayat Presensi"],
                                ] as const
                            ).map(([key, label]) => (
                                <button
                                    key={key}
                                    type="button"
                                    onClick={() => setAttendanceSubTab(key)}
                                    className={`rounded-xl py-2.5 text-xs font-bold transition ${
                                        attendanceSubTab === key
                                            ? "bg-[#F39C12] text-white shadow-xs"
                                            : "text-[#1E1915] hover:bg-[#F6F1EB]"
                                    }`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>

                        {attendanceSubTab === "today" ? (
                            activeSession ? (
                                <div className="space-y-4">
                                    <p className="text-sm font-bold text-[#1E1915]">
                                        {new Date(
                                            `${activeSession.session_date}T00:00:00`,
                                        ).toLocaleDateString("id-ID", {
                                            weekday: "long",
                                            day: "numeric",
                                            month: "short",
                                            year: "numeric",
                                        })}
                                    </p>

                                    <div className="space-y-5 rounded-3xl border border-[#EFEAE4] bg-white p-5 shadow-xs">
                                        <DigitalTimeBlocks
                                            label="Waktu Buka Presensi"
                                            timeString={windowOpenFormatted}
                                            subLabel={`Batas akhir: ${clock(activeSession.window_closes_at)} WIB`}
                                        />

                                        <p className="flex items-center justify-center gap-1.5 text-xs text-[#7C7269]">
                                            <MapPin className="h-3.5 w-3.5 shrink-0 text-[#5B4DFF]" />
                                            <span>{activeSession.location_name}</span>
                                        </p>

                                        <AgendaLocationMap
                                            latitude={activeSession.latitude}
                                            longitude={activeSession.longitude}
                                            radiusM={activeSession.radius_m}
                                        />

                                        <CheckInTrigger
                                            disabled={!windowState.isOpen}
                                            disabledLabel={windowState.label}
                                            isLocked={activeSession.is_locked}
                                            hasCheckedIn={activeSession.has_checked_in}
                                            isProcessing={isLocating}
                                            onTriggerCheckIn={handleCheckIn}
                                        />

                                        <div className="flex items-center justify-between border-t border-[#F2EDE7] pt-2 text-[11px] text-[#7C7269]">
                                            <span>
                                                Sisa Kuota Radius:{" "}
                                                <strong className="text-[#1E1915]">
                                                    {activeSession.remaining_attempts} / 3
                                                </strong>
                                            </span>
                                            <span>GPS hanya saat check-in</span>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="rounded-3xl border border-[#EFEAE4] bg-white p-8 text-center">
                                    <p className="text-sm font-bold text-[#1E1915]">
                                        Tidak ada sesi aktif
                                    </p>
                                    <p className="mt-1 text-xs text-[#7C7269]">
                                        Silakan cek kembali saat jadwal pertemuan mingguan
                                        berlangsung.
                                    </p>
                                </div>
                            )
                        ) : (
                            <div className="space-y-4">
                                <div className="flex items-center gap-2 overflow-x-auto pb-1">
                                    {[["all", "Semua Bulan"] as const, ...availableMonths].map(
                                        ([key, label]) => (
                                            <button
                                                key={key}
                                                type="button"
                                                onClick={() => setSelectedMonth(key)}
                                                className={`shrink-0 rounded-xl px-3.5 py-1.5 text-xs font-bold transition ${
                                                    selectedMonth === key
                                                        ? "border border-[#EAE4DC] bg-white text-[#5B4DFF] shadow-xs"
                                                        : "text-[#7C7269]"
                                                }`}
                                            >
                                                {label}
                                            </button>
                                        ),
                                    )}
                                </div>

                                {filteredHistory.length === 0 ? (
                                    <div className="rounded-3xl border border-[#EFEAE4] bg-white p-8 text-center">
                                        <Calendar className="mx-auto mb-2 h-8 w-8 text-[#7C7269]" />
                                        <p className="text-xs font-semibold text-[#7C7269]">
                                            Belum ada catatan kehadiran pada periode ini.
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {filteredHistory.map((item) => {
                                            const date = new Date(`${item.session_date}T00:00:00`);

                                            return (
                                                <div
                                                    key={item.id}
                                                    className="flex items-center gap-3.5 rounded-2xl border border-[#EFEAE4] bg-white p-3.5 shadow-xs"
                                                >
                                                    <div className="flex h-14 w-13 shrink-0 flex-col items-center justify-center rounded-xl bg-[#F2F0FF] text-[#5B4DFF]">
                                                        <span className="text-base leading-none font-extrabold">
                                                            {date.getDate()}
                                                        </span>
                                                        <span className="mt-1 text-[11px] font-semibold">
                                                            {date.toLocaleDateString("id-ID", {
                                                                weekday: "short",
                                                            })}
                                                        </span>
                                                    </div>

                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex items-center justify-between gap-2">
                                                            <p className="truncate text-xs font-bold text-[#1E1915]">
                                                                {item.agenda_title}
                                                            </p>
                                                            <span className="shrink-0 rounded-md bg-[#E8F8F0] px-2 py-0.5 text-[10px] font-bold text-[#0FA958]">
                                                                Hadir
                                                            </span>
                                                        </div>

                                                        <div className="mt-1.5 grid grid-cols-2 gap-2 text-[11px]">
                                                            <div>
                                                                <span className="text-[#7C7269]">
                                                                    Waktu:{" "}
                                                                </span>
                                                                <strong className="text-[#1E1915]">
                                                                    {item.checked_at} WIB
                                                                </strong>
                                                            </div>
                                                            <div>
                                                                <span className="text-[#7C7269]">
                                                                    Jarak:{" "}
                                                                </span>
                                                                <strong className="text-[#1E1915]">
                                                                    {item.distance_m !== null
                                                                        ? `${item.distance_m}m`
                                                                        : "Manual"}
                                                                </strong>
                                                            </div>
                                                        </div>

                                                        <p className="mt-1 flex items-center gap-1 truncate text-[11px] text-[#7C7269]">
                                                            <MapPin className="h-3 w-3 shrink-0" />
                                                            {item.location_name}
                                                        </p>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                )}

                <nav className="fixed bottom-0 left-1/2 z-30 flex w-full max-w-md -translate-x-1/2 items-center justify-between rounded-t-3xl border-t border-[#ECE6DF] bg-white px-4 py-3 shadow-lg">
                    {(
                        [
                            ["dashboard", "Dashboard", LayoutGrid],
                            ["today", "Presensi", Hand],
                            ["list", "Riwayat", Calendar],
                        ] as const
                    ).map(([key, label, Icon]) => {
                        const active =
                            key === "dashboard"
                                ? bottomTab === "dashboard"
                                : bottomTab === "attendance" && attendanceSubTab === key;

                        return (
                            <button
                                key={key}
                                type="button"
                                onClick={() =>
                                    key === "dashboard"
                                        ? setBottomTab("dashboard")
                                        : openAttendance(key)
                                }
                                className={`flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition ${
                                    active ? "bg-[#F39C12] text-white shadow-xs" : "text-[#7C7269]"
                                }`}
                            >
                                <Icon className="h-4 w-4" />
                                <span>{label}</span>
                            </button>
                        );
                    })}

                    <button
                        type="button"
                        onClick={() => router.post("/logout")}
                        className="rounded-xl p-2.5 text-[#7C7269] transition hover:text-[#EF4444]"
                        title="Keluar"
                        aria-label="Keluar dari akun"
                    >
                        <LogOut className="h-4 w-4" />
                    </button>
                </nav>
            </div>

            <NotificationDrawer
                open={notifOpen}
                vapidPublicKey={vapidPublicKey}
                onClose={() => setNotifOpen(false)}
                onClearedUnread={() => setUnreadCount(0)}
            />

            <FeedbackModal
                open={welcomeOpen}
                variant="welcome"
                title={`Selamat Datang, ${user.name}!`}
                message="Pastikan GPS Anda aktif saat sesi rapat berakhir untuk melakukan presensi tepat waktu."
                autoCloseMs={4000}
                onClose={() => setWelcomeOpen(false)}
            />

            <FeedbackModal
                open={feedbackModal.open}
                variant={feedbackModal.variant}
                title={feedbackModal.title}
                message={feedbackModal.message}
                onClose={() => setFeedbackModal((prev) => ({ ...prev, open: false }))}
            />
        </div>
    );
}
