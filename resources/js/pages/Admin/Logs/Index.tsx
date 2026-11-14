import { Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';

interface AuditLogItem {
    id: number;
    action: string;
    actor_name: string;
    target_name: string;
    reason: string;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface LogsProps {
    logs: {
        data: AuditLogItem[];
        links: PaginationLink[];
    };
}

export default function AdminLogsIndex({ logs }: LogsProps) {
    return (
        <AdminLayout title="Log Audit">
            <div className="mb-6">
                <h1 className="text-xl font-extrabold text-[#1E1915]">
                    Log Audit Presensi Manual & Reset Sandi
                </h1>
                <p className="mt-1 text-xs text-[#7C7269]">
                    Catatan permanen (tidak dapat diubah) untuk menjaga
                    akuntabilitas pengurus.
                </p>
            </div>

            <div className="overflow-hidden rounded-3xl border border-[#EFEAE4] bg-white">
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr className="border-b border-[#EAE4DC] bg-[#F39C12] text-white">
                                <th className="p-3.5 font-bold">Waktu</th>
                                <th className="p-3.5 font-bold">Tindakan</th>
                                <th className="p-3.5 font-bold">Admin</th>
                                <th className="p-3.5 font-bold">Anggota</th>
                                <th className="p-3.5 font-bold">Alasan</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#F2EDE7]">
                            {logs.data.map((log) => (
                                <tr key={log.id} className="hover:bg-[#FAF7F2]">
                                    <td className="p-3.5 whitespace-nowrap text-[#7C7269]">
                                        {log.created_at} WIB
                                    </td>
                                    <td className="p-3.5">
                                        <span
                                            className={`rounded-lg px-2.5 py-1 font-bold ${
                                                log.action ===
                                                'manual_attendance'
                                                    ? 'bg-[#E8F8F0] text-[#0FA958]'
                                                    : 'bg-[#FFF4E5] text-[#F39C12]'
                                            }`}
                                        >
                                            {log.action === 'manual_attendance'
                                                ? 'Presensi Manual'
                                                : 'Reset Password'}
                                        </span>
                                    </td>
                                    <td className="p-3.5 font-bold text-[#1E1915]">
                                        {log.actor_name}
                                    </td>
                                    <td className="p-3.5 font-semibold text-[#1E1915]">
                                        {log.target_name}
                                    </td>
                                    <td className="p-3.5 text-[#7C7269]">
                                        {log.reason}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {logs.data.length === 0 && (
                    <p className="py-8 text-center text-xs text-[#7C7269]">
                        Belum ada catatan audit.
                    </p>
                )}
            </div>

            {logs.links.length > 3 && (
                <div className="mt-4 flex flex-wrap gap-1.5">
                    {logs.links.map((link, idx) =>
                        link.url ? (
                            <Link
                                key={idx}
                                href={link.url}
                                preserveScroll
                                className={`rounded-lg px-3 py-1.5 text-xs font-bold ${
                                    link.active
                                        ? 'bg-[#F39C12] text-white'
                                        : 'bg-white text-[#7C7269]'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ) : (
                            <span
                                key={idx}
                                className="rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-[#B8AFA5]"
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ),
                    )}
                </div>
            )}
        </AdminLayout>
    );
}
