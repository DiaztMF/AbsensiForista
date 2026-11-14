import { useForm } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';

interface Settings {
    default_radius_m: number;
    default_window_minutes: number;
    gps_accuracy_ceiling_m: number;
    remind_before_start_minutes: number;
    remind_on_window_open: boolean;
    remind_before_close_minutes: number;
}

export default function AdminSettingsEdit({
    settings,
}: {
    settings: Settings;
}) {
    const form = useForm({
        default_radius_m: settings.default_radius_m,
        default_window_minutes: settings.default_window_minutes,
        gps_accuracy_ceiling_m: settings.gps_accuracy_ceiling_m,
        remind_before_start_minutes: settings.remind_before_start_minutes,
        remind_on_window_open: Boolean(settings.remind_on_window_open),
        remind_before_close_minutes: settings.remind_before_close_minutes,
    });

    const field = 'h-10 w-full rounded-xl border border-[#E2DAD0] px-3 text-xs';

    return (
        <AdminLayout title="Pengaturan">
            <div className="max-w-xl">
                <h1 className="text-xl font-extrabold text-[#1E1915]">
                    Pengaturan Sistem
                </h1>
                <p className="mt-1 mb-6 text-xs text-[#7C7269]">
                    Nilai default ini berlaku untuk agenda dan sesi pertemuan
                    mendatang.
                </p>

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put('/admin/settings', { preserveScroll: true });
                    }}
                    className="space-y-4 rounded-3xl border border-[#EFEAE4] bg-white p-6 shadow-2xs"
                >
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label className="mb-1 block text-xs font-bold">
                                Radius Default (m)
                            </label>
                            <input
                                type="number"
                                min={5}
                                max={100}
                                value={form.data.default_radius_m}
                                onChange={(e) =>
                                    form.setData(
                                        'default_radius_m',
                                        Number(e.target.value),
                                    )
                                }
                                className={field}
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-xs font-bold">
                                Durasi Window (mnt)
                            </label>
                            <input
                                type="number"
                                min={5}
                                max={120}
                                value={form.data.default_window_minutes}
                                onChange={(e) =>
                                    form.setData(
                                        'default_window_minutes',
                                        Number(e.target.value),
                                    )
                                }
                                className={field}
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-xs font-bold">
                                Batas Akurasi GPS (m)
                            </label>
                            <input
                                type="number"
                                min={5}
                                max={100}
                                value={form.data.gps_accuracy_ceiling_m}
                                onChange={(e) =>
                                    form.setData(
                                        'gps_accuracy_ceiling_m',
                                        Number(e.target.value),
                                    )
                                }
                                className={field}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 border-t border-[#F2EDE7] pt-2 sm:grid-cols-2">
                        <div>
                            <label className="mb-1 block text-xs font-bold">
                                Pengingat Sebelum Rapat (menit)
                            </label>
                            <input
                                type="number"
                                min={0}
                                max={180}
                                value={form.data.remind_before_start_minutes}
                                onChange={(e) =>
                                    form.setData(
                                        'remind_before_start_minutes',
                                        Number(e.target.value),
                                    )
                                }
                                className={field}
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-xs font-bold">
                                Pengingat Sebelum Window Tutup (menit)
                            </label>
                            <input
                                type="number"
                                min={1}
                                max={60}
                                value={form.data.remind_before_close_minutes}
                                onChange={(e) =>
                                    form.setData(
                                        'remind_before_close_minutes',
                                        Number(e.target.value),
                                    )
                                }
                                className={field}
                            />
                        </div>
                    </div>

                    <label className="flex cursor-pointer items-center gap-2 pt-1 text-xs font-semibold">
                        <input
                            type="checkbox"
                            checked={form.data.remind_on_window_open}
                            onChange={(e) =>
                                form.setData(
                                    'remind_on_window_open',
                                    e.target.checked,
                                )
                            }
                            className="h-4 w-4 rounded text-[#F39C12]"
                        />
                        <span>
                            Kirim notifikasi otomatis tepat saat window presensi
                            dibuka
                        </span>
                    </label>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="h-11 w-full rounded-xl bg-[#F39C12] text-xs font-bold text-white shadow-xs transition hover:bg-[#DF8C09] disabled:opacity-60"
                    >
                        Simpan Pengaturan
                    </button>
                </form>
            </div>
        </AdminLayout>
    );
}
