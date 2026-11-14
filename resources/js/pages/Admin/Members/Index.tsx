import { router, useForm } from "@inertiajs/react";
import { KeyRound, Power, Trash2, UserPlus } from "lucide-react";
import { useState, useEffect, useRef } from "react";
import AdminLayout from "@/layouts/admin-layout";

interface Division {
    id: number;
    name: string;
}

interface Member {
    id: number;
    name: string;
    username: string;
    role: string;
    is_active: boolean;
    division: Division;
    stats: { percentage: number; score: number };
}

interface Filters {
    search: string;
    division_id: number | null;
    page?: number;
}

interface MembersProps {
    members: {
        data: Member[];
        current_page: number;
        last_page: number;
    };
    divisions: Division[];
    filters: Filters;
}

export default function AdminMembersIndex({
    members,
    divisions,
    filters,
}: MembersProps) {
    const [resetTarget, setResetTarget] = useState<Member | null>(null);
    const [search, setSearch] = useState(filters.search);
    const debounceTimer = useRef<number | null>(null);

    const memberForm = useForm({
        name: "",
        username: "",
        division_id: divisions[0]?.id ?? "",
        role: "member",
        password: "",
    });

    const divisionForm = useForm({ name: "" });
    const resetForm = useForm({ password: "", reason: "" });

    const applyFilter = (patch: Partial<Filters>) => {
        router.get(
            "/admin/members",
            { ...filters, ...patch },
            { preserveState: true, preserveScroll: true },
        );
    };

    useEffect(() => {
        if (debounceTimer.current) {
            window.clearTimeout(debounceTimer.current);
        }
        debounceTimer.current = window.setTimeout(() => {
            applyFilter({ search });
        }, 500);
        return () => {
            if (debounceTimer.current) {
                window.clearTimeout(debounceTimer.current);
            }
        };
    }, [search]);


    const field = "h-10 w-full rounded-xl border border-[#E2DAD0] px-3 text-xs";

    return (
        <AdminLayout title="Anggota & Divisi">
            <div className="space-y-6">
                <div className="grid grid-cols-2 gap-6">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            memberForm.post("/admin/members", {
                                preserveScroll: true,
                                onSuccess: () =>
                                    memberForm.reset(
                                        "name",
                                        "username",
                                        "password",
                                    ),
                            });
                        }}
                        className="flex flex-col space-y-6 rounded-3xl border border-[#EFEAE4] bg-white p-5 shadow-2xs"
                    >
                        <h2 className="flex items-center gap-2 text-sm font-extrabold text-[#1E1915]">
                            <UserPlus className="h-4 w-4 text-[#F39C12]" />
                            <span>Buat Akun Anggota</span>
                        </h2>

                        <input
                            type="text"
                            required
                            placeholder="Nama Lengkap"
                            value={memberForm.data.name}
                            onChange={(e) =>
                                memberForm.setData("name", e.target.value)
                            }
                            className={field}
                        />
                        <input
                            type="text"
                            required
                            placeholder="Username (tanpa spasi)"
                            value={memberForm.data.username}
                            onChange={(e) =>
                                memberForm.setData("username", e.target.value)
                            }
                            className={field}
                        />
                        <select
                            value={memberForm.data.division_id}
                            onChange={(e) =>
                                memberForm.setData(
                                    "division_id",
                                    Number(e.target.value),
                                )
                            }
                            className={`${field} bg-white`}
                        >
                            {divisions.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </select>
                        <select
                            value={memberForm.data.role}
                            onChange={(e) =>
                                memberForm.setData("role", e.target.value)
                            }
                            className={`${field} bg-white`}
                        >
                            <option value="member">Member (Anggota)</option>
                            <option value="admin">Admin (Pengurus)</option>
                        </select>
                        <input
                            type="text"
                            required
                            placeholder="Kata sandi awal (min. 6 karakter)"
                            value={memberForm.data.password}
                            onChange={(e) =>
                                memberForm.setData("password", e.target.value)
                            }
                            className={field}
                        />

                        {Object.keys(memberForm.errors).length > 0 && (
                            <p className="text-[11px] font-medium text-[#EF4444]">
                                {Object.values(memberForm.errors)[0]}
                            </p>
                        )}

                        <button
                            type="submit"
                            disabled={memberForm.processing}
                            className="h-10 w-full rounded-xl bg-[#F39C12] text-xs font-bold text-white disabled:opacity-60"
                        >
                            Tambah Akun
                        </button>
                    </form>
                    <div className="space-y-3 rounded-3xl border border-[#EFEAE4] bg-white p-5 shadow-2xs">
                        <h2 className="text-sm font-extrabold text-[#1E1915]">
                            Daftar Divisi
                        </h2>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                divisionForm.post("/admin/divisions", {
                                    preserveScroll: true,
                                    onSuccess: () => divisionForm.reset(),
                                });
                            }}
                            className="flex gap-2"
                        >
                            <input
                                type="text"
                                required
                                placeholder="Nama divisi baru..."
                                value={divisionForm.data.name}
                                onChange={(e) =>
                                    divisionForm.setData("name", e.target.value)
                                }
                                className="h-9 flex-1 rounded-xl border border-[#E2DAD0] px-3 text-xs"
                            />
                            <button
                                type="submit"
                                className="h-9 rounded-xl bg-[#F39C12] px-3.5 text-xs font-bold text-white"
                            >
                                Tambah
                            </button>
                        </form>

                        <div className="divide-y divide-[#F2EDE7] text-xs">
                            {divisions.map((d) => (
                                <div
                                    key={d.id}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span className="font-semibold">
                                        {d.name}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.delete(
                                                `/admin/divisions/${d.id}`,
                                                {
                                                    preserveScroll: true,
                                                },
                                            )
                                        }
                                        className="p-1.5 rounded-lg bg-[#F6F1EB] text-[#1E1915] hover:bg-[#E2DAD0]"
                                        title="Hapus divisi (hanya jika kosong)"
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
            <div className="space-y-4">
                <div className="mt-6 flex flex-col gap-3 rounded-3xl border border-[#EFEAE4] bg-white p-4 sm:flex-row">
                    <input
                        type="search"
                        placeholder="Cari nama atau username lalu Enter..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === "Enter") {
                                applyFilter({
                                    search: e.currentTarget.value,
                                });
                            }
                        }}
                        className="h-10 flex-1 rounded-xl border border-[#E2DAD0] px-3.5 text-xs"
                    />
                    <select
                        value={filters.division_id ?? ""}
                        onChange={(e) =>
                            applyFilter({
                                division_id: e.target.value
                                    ? Number(e.target.value)
                                    : null,
                            })
                        }
                        className="h-10 rounded-xl border border-[#E2DAD0] bg-white px-3 text-xs"
                    >
                        <option value="">Semua Divisi</option>
                        {divisions.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="overflow-hidden rounded-3xl border border-[#EFEAE4] bg-white">
                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse text-left text-xs">
                            <thead>
                                <tr className="border-b border-[#EAE4DC] bg-[#F39C12] text-white">
                                    <th className="p-3.5 font-bold">Anggota</th>
                                    <th className="p-3.5 font-bold">Divisi</th>
                                    <th className="p-3.5 text-center font-bold">
                                        Kehadiran & Skor
                                    </th>
                                    <th className="p-3.5 text-center font-bold">
                                        Status
                                    </th>
                                    <th className="p-3.5 text-center font-bold">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-[#F2EDE7]">
                                {members.data.map((m) => (
                                    <tr
                                        key={m.id}
                                        className="hover:bg-[#FAF7F2]"
                                    >
                                        <td className="p-3.5">
                                            <p className="font-bold text-[#1E1915]">
                                                {m.name}
                                            </p>
                                            <p className="text-[11px] text-[#7C7269]">
                                                @{m.username} •{" "}
                                                {m.role.toUpperCase()}
                                            </p>
                                        </td>
                                        <td className="p-3.5 text-[#7C7269]">
                                            {m.division.name}
                                        </td>
                                        <td className="p-3.5 text-center">
                                            <span className="font-bold text-[#1E1915]">
                                                {m.stats.percentage}%
                                            </span>{" "}
                                            <span className="ml-1 rounded bg-[#EEF0FF] px-2 py-0.5 font-extrabold text-[#5B4DFF]">
                                                {m.stats.score.toFixed(1)}
                                            </span>
                                        </td>
                                        <td className="p-3.5 text-center">
                                            <span
                                                className={`rounded-full px-2.5 py-0.5 text-[11px] font-bold ${
                                                    m.is_active
                                                        ? "bg-[#E8F8F0] text-[#0FA958]"
                                                        : "bg-[#FDECEE] text-[#EF4444]"
                                                }`}
                                            >
                                                {m.is_active
                                                    ? "Aktif"
                                                    : "Nonaktif"}
                                            </span>
                                        </td>
                                        <td className="p-3.5">
                                            <div className="flex justify-center items-center gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setResetTarget(m);
                                                        resetForm.reset();
                                                    }}
                                                    className="flex items-center gap-1.5 rounded-lg bg-[#F6F1EB] px-2.5 py-1.5 text-xs font-bold text-[#1E1915] hover:bg-[#E2DAD0]"
                                                    title="Reset kata sandi"
                                                >
                                                    <KeyRound className="h-3.5 w-3.5" />
                                                    <span>Reset Sandi</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        router.post(
                                                            `/admin/members/${m.id}/toggle-active`,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                    className="flex items-center gap-1.5 rounded-lg bg-[#F6F1EB] px-2.5 py-1.5 text-xs font-bold text-[#1E1915] hover:bg-[#E2DAD0]"
                                                    title={
                                                        m.is_active
                                                            ? 'Nonaktifkan'
                                                            : 'Aktifkan'
                                                    }
                                                >
                                                    <Power className="h-3.5 w-3.5" />
                                                    <span>{m.is_active ? 'Nonaktifkan' : 'Aktifkan'}</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    {members.last_page && members.last_page > 1 && (
                        <div className="flex items-center justify-between gap-2 px-4 py-3">
                            <p className="text-xs text-[#7C7269]">
                                Halaman {members.current_page} dari {members.last_page}
                            </p>
                            <div className="flex gap-1">
                                <button
                                    type="button"
                                    disabled={members.current_page <= 1}
                                    onClick={() =>
                                        applyFilter({
                                        page: members.current_page - 1,
                                    })
                                    }
                                    className="rounded-lg bg-[#F6F1EB] px-2.5 py-1.5 text-xs font-bold text-[#1E1915] hover:bg-[#E2DAD0] disabled:opacity-50"
                                >
                                    ←
                                </button>
                                <button
                                    type="button"
                                    disabled={members.current_page >= members.last_page}
                                    onClick={() =>
                                        applyFilter({
                                        page: members.current_page + 1,
                                    })
                                    }
                                    className="rounded-lg bg-[#F6F1EB] px-2.5 py-1.5 text-xs font-bold text-[#1E1915] hover:bg-[#E2DAD0] disabled:opacity-50"
                                >
                                    →
                                </button>
                            </div>
                        </div>
                    )}
                </div>

                    {members.data.length === 0 && (
                        <p className="py-8 text-center text-xs text-[#7C7269]">
                            Tidak ada anggota yang cocok dengan filter.
                        </p>
                    )}
                </div>
            </div>

            {resetTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-4 backdrop-blur-xs">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            resetForm.post(
                                `/admin/members/${resetTarget.id}/reset-password`,
                                {
                                    preserveScroll: true,
                                    onSuccess: () => setResetTarget(null),
                                },
                            );
                        }}
                        className="w-full max-w-sm space-y-4 rounded-3xl bg-white p-6 shadow-xl"
                    >
                        <h3 className="text-base font-extrabold text-[#1E1915]">
                            Reset Sandi — {resetTarget.name}
                        </h3>
                        <input
                            type="text"
                            required
                            minLength={6}
                            placeholder="Kata sandi sementara baru..."
                            value={resetForm.data.password}
                            onChange={(e) =>
                                resetForm.setData("password", e.target.value)
                            }
                            className={field}
                        />
                        <input
                            type="text"
                            placeholder="Catatan / alasan reset (opsional)"
                            value={resetForm.data.reason}
                            onChange={(e) =>
                                resetForm.setData("reason", e.target.value)
                            }
                            className={field}
                        />
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setResetTarget(null)}
                                className="rounded-xl bg-[#F6F1EB] px-4 py-2 text-xs font-bold text-[#7C7269]"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={resetForm.processing}
                                className="rounded-xl bg-[#F39C12] px-4 py-2 text-xs font-bold text-white disabled:opacity-60"
                            >
                                Simpan Sandi Baru
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </AdminLayout>
    );
}
