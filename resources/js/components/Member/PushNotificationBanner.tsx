import { BellRing, CheckCircle2, Share, Smartphone } from "lucide-react";
import { useEffect, useState } from "react";

interface PushNotificationBannerProps {
    vapidPublicKey: string | null;
    onSubscribed?: () => void;
}

type Mode = "loading" | "ios_install" | "denied" | "ready" | "subscribed" | "unsupported";

function urlBase64ToUint8Array(base64String: string): Uint8Array<ArrayBuffer> {
    const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
    const raw = window.atob(base64);
    const bytes = new Uint8Array(raw.length);

    for (let i = 0; i < raw.length; i++) {
        bytes[i] = raw.charCodeAt(i);
    }

    return bytes;
}

const csrfToken = () =>
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? "";

export default function PushNotificationBanner({
    vapidPublicKey,
    onSubscribed,
}: PushNotificationBannerProps) {
    const [mode, setMode] = useState<Mode>("loading");
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !("MSStream" in window);
        const isStandalone =
            window.matchMedia("(display-mode: standalone)").matches || "standalone" in navigator;

        // F6 PRD: iPhone tanpa install PWA mendapat instruksi, bukan tombol izin
        if (isIos && !isStandalone) {
            setMode("ios_install");

            return;
        }

        if (!("serviceWorker" in navigator) || !("PushManager" in window) || !vapidPublicKey) {
            setMode("unsupported");

            return;
        }

        let active = true;

        // SW harus terdaftar sebelum `ready` bisa resolve; tanpa ini banner tertahan di 'loading'
        void navigator.serviceWorker
            .register("/sw.js")
            .then(() => navigator.serviceWorker.ready)
            .then((registration) => registration.pushManager.getSubscription())
            .then((existing) => {
                if (active) {
                    setMode(existing ? "subscribed" : "ready");
                }
            })
            .catch(() => {
                if (active) {
                    setMode("ready");
                }
            });

        return () => {
            active = false;
        };
    }, [vapidPublicKey]);

    const handleSubscribe = async () => {
        if (!vapidPublicKey || busy) {
            return;
        }

        setBusy(true);

        try {
            const permission = await Notification.requestPermission();
            if (permission !== "granted") {
                setMode("denied");

                return;
            }

            const registration = await navigator.serviceWorker.getRegistration("/sw.js");
            const readyRegistration = registration ?? (await navigator.serviceWorker.ready);

            const subscription = await readyRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });

            const json = subscription.toJSON();

            const response = await fetch("/push-subscriptions", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    endpoint: json.endpoint,
                    keys: json.keys,
                    contentEncoding: "aesgcm",
                }),
            });

            if (response.ok) {
                setMode("subscribed");
                onSubscribed?.();
            }
        } catch {
            // Kegagalan push tidak boleh memblokir alur presensi
        } finally {
            setBusy(false);
        }
    };

    if (mode === "loading" || mode === "unsupported") {
        return null;
    }

    if (mode === "ios_install") {
        return (
            <div className="rounded-2xl border border-[#F39C12]/35 bg-[#FFF8EB] p-3.5 text-xs text-[#1E1915]">
                <div className="flex items-start gap-2.5">
                    <Smartphone className="mt-0.5 h-4 w-4 shrink-0 text-[#F39C12]" />
                    <div className="space-y-1">
                        <p className="font-bold text-[#5C3806]">
                            Aktifkan Pengingat di iPhone (PWA)
                        </p>
                        <p className="leading-relaxed text-[#7C7269]">
                            Untuk menerima notifikasi pengingat rapat di iPhone, ketuk ikon{" "}
                            <span className="inline-flex items-center font-semibold text-[#1E1915]">
                                Bagikan <Share className="mx-0.5 h-3 w-3" />
                            </span>{" "}
                            di Safari lalu pilih <strong>Tambah ke Layar Utama</strong>, kemudian
                            buka aplikasi dari Layar Utama.
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    if (mode === "denied") {
        return (
            <div className="rounded-2xl border border-[#EF4444]/25 bg-[#FDECEE] px-3.5 py-2.5 text-[11px] leading-relaxed text-[#7C7269]">
                <strong className="text-[#EF4444]">Izin notifikasi diblokir.</strong> Aktifkan lewat
                ikon gembok di bilah alamat, lalu muat ulang halaman ini.
            </div>
        );
    }

    if (mode === "subscribed") {
        return (
            <div className="flex items-center gap-2 rounded-2xl border border-[#0FA958]/25 bg-[#E8F8F0] px-3.5 py-2.5 text-xs font-semibold text-[#0FA958]">
                <CheckCircle2 className="h-4 w-4 shrink-0" />
                <span>Pengingat push aktif di perangkat ini.</span>
            </div>
        );
    }

    return (
        <div className="flex items-center justify-between gap-3 rounded-2xl border border-[#5B4DFF]/25 bg-[#EEF0FF] p-3.5">
            <div className="flex items-start gap-2.5">
                <BellRing className="mt-0.5 h-4 w-4 shrink-0 text-[#5B4DFF]" />
                <div>
                    <p className="text-xs font-bold text-[#1E1915]">
                        Aktifkan Notifikasi Pengingat
                    </p>
                    <p className="text-[11px] text-[#7C7269]">
                        Dapatkan pengingat 30 menit sebelum rapat & saat presensi dibuka.
                    </p>
                </div>
            </div>
            <button
                type="button"
                disabled={busy}
                onClick={handleSubscribe}
                className="shrink-0 rounded-xl bg-[#5B4DFF] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#493be6] disabled:opacity-60"
            >
                {busy ? "Memuat..." : "Aktifkan"}
            </button>
        </div>
    );
}
