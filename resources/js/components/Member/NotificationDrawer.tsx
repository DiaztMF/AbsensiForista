import { Bell, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import PushNotificationBanner from './PushNotificationBanner';

interface NotificationItem {
    id: string;
    title: string;
    body: string;
    read_at: string | null;
    created_at: string;
}

interface NotificationDrawerProps {
    open: boolean;
    vapidPublicKey: string | null;
    onClose: () => void;
    onClearedUnread: () => void;
}

const csrfToken = () =>
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content ?? '';

export default function NotificationDrawer({
    open,
    vapidPublicKey,
    onClose,
    onClearedUnread,
}: NotificationDrawerProps) {
    const [items, setItems] = useState<NotificationItem[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        let active = true;
        setLoading(true);

        void fetch('/notifications', {
            headers: { Accept: 'application/json' },
        })
            .then((res) => res.json())
            .then(
                (data: {
                    notifications?: NotificationItem[];
                    unread_count?: number;
                }) => {
                    if (!active) {
                        return;
                    }

                    setItems(data.notifications ?? []);

                    // F6 PRD: membuka lonceng langsung menghapus badge belum dibaca
                    if ((data.unread_count ?? 0) > 0) {
                        void fetch('/notifications/read-all', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken(),
                                Accept: 'application/json',
                            },
                        }).then(() => {
                            if (active) {
                                onClearedUnread();
                            }
                        });
                    }
                },
            )
            .finally(() => {
                if (active) {
                    setLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, [open, onClearedUnread]);

    if (!open) {
        return null;
    }

    return (
        <div
            onClick={onClose}
            className="fixed inset-0 z-50 flex items-end justify-center bg-black/45 backdrop-blur-xs sm:items-center"
        >
            <div
                onClick={(e) => e.stopPropagation()}
                className="flex max-h-[82dvh] w-full max-w-md flex-col rounded-t-3xl bg-[#F6F1EB] p-5 shadow-xl sm:rounded-3xl"
            >
                <div className="flex items-center justify-between border-b border-[#E5DDD3] pb-3">
                    <div className="flex items-center gap-2">
                        <Bell className="h-4 w-4 text-[#5C3806]" />
                        <h3 className="text-sm font-bold text-[#1E1915]">
                            Notifikasi & Pengingat
                        </h3>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-[#7C7269] hover:text-[#1E1915]"
                        aria-label="Tutup notifikasi"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <div className="mt-3">
                    <PushNotificationBanner vapidPublicKey={vapidPublicKey} />
                </div>

                <div className="mt-3 flex-1 space-y-2.5 overflow-y-auto pr-0.5">
                    {loading ? (
                        <p className="py-8 text-center text-xs text-[#7C7269]">
                            Memuat notifikasi...
                        </p>
                    ) : items.length === 0 ? (
                        <div className="rounded-2xl border border-[#EFEAE4] bg-white p-6 text-center">
                            <p className="text-xs font-semibold text-[#7C7269]">
                                Belum ada pengingat rapat masuk.
                            </p>
                        </div>
                    ) : (
                        items.map((item) => (
                            <div
                                key={item.id}
                                className="rounded-2xl border border-[#EFEAE4] bg-white p-3.5 shadow-2xs"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <p className="text-xs font-bold text-[#1E1915]">
                                        {item.title}
                                    </p>
                                    <span className="shrink-0 text-[10px] text-[#7C7269]">
                                        {item.created_at}
                                    </span>
                                </div>
                                <p className="mt-1 text-xs leading-relaxed text-[#7C7269]">
                                    {item.body}
                                </p>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </div>
    );
}
