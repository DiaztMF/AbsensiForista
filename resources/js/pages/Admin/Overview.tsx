import { Link } from '@inertiajs/react';
import { Calendar, Clock, MapPin, Users } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import AdminLayout from '@/layouts/admin-layout';

interface FocusSession {
    id: number;
    agenda_id: number;
    title: string;
    location_name: string;
    session_date: string;
    starts_at: string;
    ends_at: string;
    window_opens_at: string;
    window_closes_at: string;
    present_count: number;
    active_members_count: number;
}

interface OverviewProps {
    serverNow: string;
    focusSession: FocusSession | null;
    activeMembersCount: number;
}

const clock = (iso: string) =>
    new Date(iso).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });

export default function AdminOverview({
    serverNow,
    focusSession,
    activeMembersCount,
}: OverviewProps) {
    const [nowMs, setNowMs] = useState(() => new Date(serverNow).getTime());

    useEffect(() => {
        setNowMs(new Date(serverNow).getTime());
        const timer = setInterval(() => setNowMs((prev) => prev + 1000), 1000);

        return () => clearInterval(timer);
    }, [serverNow]);

    const countdown = useMemo(() => {
        if (!focusSession) {
            return null;
        }

        const opensMs = new Date(focusSession.window_opens_at).getTime();
        const closesMs = new Date(focusSession.window_closes_at).getTime();

        const formatDiff = (ms: number) => {
            const total = Math.max(0, Math.floor(ms / 1000));
            const h = String(Math.floor(total / 3600)).padStart(2, '0');
            const m = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
            const s = String(total % 60).padStart(2, '0');

            return `${h}:${m}:${s}`;
        };

        if (nowMs < opensMs) {
            return {
                badge: 'Menunggu Rapat Selesai',
                label: 'Window Presensi Dibuka Dalam',
                timer: formatDiff(opensMs - nowMs),
                color: 'bg-[#FFF4E5] text-[#F39C12]',
            };
        }

        if (nowMs <= closesMs) {
            return {
                badge: 'Window Presensi Aktif',
                label: 'Sisa Waktu Check-In',
                timer: formatDiff(closesMs - nowMs),
                color: 'bg-[#E8F8F0] text-[#0FA958]',
            };
        }

        return {
            badge: 'Sesi Selesai',
            label: 'Window Presensi Telah Ditutup',
            timer: '00:00:00',
            color: 'bg-[#F4F3F8] text-[#7C7269]',
        };
    }, [focusSession, nowMs]);

    const presentPercentage = focusSession
        ? focusSession.active_members_count > 0
            ? Math.round(
                  (focusSession.present_count /
                      focusSession.active_members_count) *
                      100,
              )
            : 0
        : 0;

    return (
        <AdminLayout title="Ringkasan">
            <div className="mb-6">
                <h1 className="text-xl font-extrabold text-[#1E1915]">
                    Ringkasan Presensi Hari Ini
                </h1>
                <p className="mt-1 text-xs text-[#7C7269]">
                    Pantau kehadiran anggota secara langsung dan kelola sesi
                    pertemuan mingguan.
                </p>
            </div>

            {focusSession && countdown ? (
                <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div className="flex flex-col justify-between rounded-3xl border border-[#EFEAE4] bg-white p-6 shadow-xs lg:col-span-2">
                        <div>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <span
                                    className={`rounded-full px-3 py-1 text-xs font-bold ${countdown.color}`}
                                >
                                    {countdown.badge}
                                </span>
                                <span className="flex items-center gap-1 text-xs font-semibold text-[#7C7269]">
                                    <Calendar className="h-3.5 w-3.5" />
                                    {focusSession.session_date}
                                </span>
                            </div>

                            <h2 className="mt-4 text-lg font-extrabold text-[#1E1915]">
                                {focusSession.title}
                            </h2>
                            <p className="mt-1 flex items-center gap-1.5 text-xs text-[#7C7269]">
                                <MapPin className="h-3.5 w-3.5 text-[#5B4DFF]" />
                                {focusSession.location_name}
                            </p>
                        </div>

                        <div className="my-6 rounded-2xl bg-[#F6F1EB] p-5 text-center">
                            <p className="text-xs font-semibold text-[#7C7269]">
                                {countdown.label}
                            </p>
                            <p className="mt-1 font-mono text-3xl font-extrabold tracking-wider text-[#1E1915]">
                                {countdown.timer}
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <span className="flex items-center gap-1 text-xs text-[#7C7269]">
                                <Clock className="h-3.5 w-3.5" />
                                Window: {clock(
                                    focusSession.window_opens_at,
                                )} -{' '}
                                {clock(focusSession.window_closes_at)} WIB
                            </span>

                            <Link
                                href={`/admin/recaps?agenda_id=${focusSession.agenda_id}&session_id=${focusSession.id}`}
                                className="rounded-xl bg-[#F39C12] px-4 py-2.5 text-xs font-bold text-white transition hover:bg-[#DF8C09]"
                            >
                                Buka Rekap & Tindak Lanjut →
                            </Link>
                        </div>
                    </div>

                    <div className="flex flex-col justify-between rounded-3xl border border-[#EFEAE4] bg-white p-6 shadow-xs">
                        <div>
                            <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-[#E8F8F0] text-[#0FA958]">
                                <Users className="h-5 w-5" />
                            </div>
                            <p className="text-xs font-bold text-[#7C7269]">
                                Kehadiran Saat Ini
                            </p>
                            <div className="mt-2 flex items-baseline gap-2">
                                <span className="text-4xl font-extrabold text-[#0FA958]">
                                    {focusSession.present_count}
                                </span>
                                <span className="text-sm font-bold text-[#7C7269]">
                                    / {focusSession.active_members_count}{' '}
                                    Anggota Aktif
                                </span>
                            </div>
                        </div>

                        <div className="mt-6 space-y-2">
                            <div className="h-3 w-full overflow-hidden rounded-full bg-[#F4F3F8]">
                                <div
                                    className="h-full bg-[#0FA958] transition-all duration-500"
                                    style={{ width: `${presentPercentage}%` }}
                                />
                            </div>
                            <p className="text-xs text-[#7C7269]">
                                Belum hadir:{' '}
                                <strong className="text-[#EF4444]">
                                    {Math.max(
                                        0,
                                        focusSession.active_members_count -
                                            focusSession.present_count,
                                    )}{' '}
                                    anggota
                                </strong>
                            </p>
                        </div>
                    </div>
                </div>
            ) : (
                <div className="rounded-3xl border border-[#EFEAE4] bg-white p-8 text-center">
                    <p className="text-sm font-bold text-[#1E1915]">
                        Belum ada sesi pertemuan terdekat
                    </p>
                    <p className="mt-1 mb-4 text-xs text-[#7C7269]">
                        Total anggota aktif terdaftar saat ini:{' '}
                        {activeMembersCount} orang.
                    </p>
                    <Link
                        href="/admin/agendas"
                        className="inline-flex rounded-xl bg-[#F39C12] px-4 py-2.5 text-xs font-bold text-white"
                    >
                        Kelola Agenda Mingguan
                    </Link>
                </div>
            )}
        </AdminLayout>
    );
}
