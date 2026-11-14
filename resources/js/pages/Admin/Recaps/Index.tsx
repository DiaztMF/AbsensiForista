import { router, useForm } from '@inertiajs/react';
import { Bell, Download, Lock, PlusCircle } from 'lucide-react';
import { useState } from 'react';
import AdminLayout from '@/layouts/admin-layout';

interface Option {
    id: number;
    title?: string;
    name?: string;
}

interface SessionOption {
    id: number;
    session_date: string;
    status: string;
}

interface RecapRow {
    user_id: number;
    name: string;
    division: string;
    status: 'hadir' | 'belum_hadir';
    checked_at: string;
    distance_m: number | null;
    source: string | null;
    manual_reason: string | null;
    created_by_name: string | null;
    failed_attempts: number;
    is_locked: boolean;
}

interface RangeRow {
    user_id: number;
    name: string;
    division: string;
    total_sessions: number;
    attended_count: number;
    percentage: number;
    score: number;
}

interface Filters {
    agenda_id: number | null;
    session_id: number | null;
    division_id: number | null;
    from_date: string | null;
    to_date: string | null;
}

interface RecapsProps {
    agendas: Option[];
    divisions: Option[];
    sessions: SessionOption[];
    activeSession: { id: number; agenda: { title: string } } | null;
    sessionRows: RecapRow[];
    rangeRows: RangeRow[];
    filters: Filters;
}

type Tab = 'session' | 'pending' | 'range';

export default function AdminRecapsIndex({
    agendas,
    divisions,
    sessions,
    activeSession,
    sessionRows,
    rangeRows,
    filters,
}: RecapsProps) {
    const [tab, setTab] = useState<Tab>('session');
    const [manualTarget, setManualTarget] = useState<{
        id: number;
        name: string;
    } | null>(null);

    const manualForm = useForm({ user_id: 0, reason: '' });

    const applyFilter = (patch: Record<string, unknown>) => {
        router.get(
            '/admin/recaps',
            { ...filters, ...patch },
            { preserveState: true, preserveScroll: true },
        );
    };

    const openManualModal = (row: RecapRow) => {
        setManualTarget({ id: row.user_id, name: row.name });
        manualForm.setData({ user_id: row.user_id, reason: '' });
    };

    const submitManualAttendance = (e: React.FormEvent) => {
        e.preventDefault();
        if (!activeSession) {
            return;
        }

        manualForm.post(`/admin/recaps/${activeSession.id}/manual`, {
            preserveScroll: true,
            onSuccess: () => setManualTarget(null),
        });
    };

    const exportUrl = (format: 'xlsx' | 'pdf') => {
        const params = new URLSearchParams({
            mode: tab === 'range' ? 'range' : 'session',
        });

        if (filters.agenda_id) {
            params.set('agenda_id', String(filters.agenda_id));
        }
        if (filters.session_id) {
            params.set('session_id', String(filters.session_id));
        }
        if (filters.division_id) {
            params.set('division_id', String(filters.division_id));
        }
        if (filters.from_date) {
            params.set('from_date', filters.from_date);
        }
        if (filters.to_date) {
            params.set('to_date', filters.to_date);
        }

        return `/admin/recaps/export/${format}?${params.toString()}`;
    };

    const pendingRows = sessionRows.filter((r) => r.status === 'belum_hadir');
    const presentCount = sessionRows.length - pendingRows.length;
    const rows = tab === 'pending' ? pendingRows : sessionRows;

    const fieldClass =
        'h-10 w-full rounded-xl border border-[#E2DAD0] bg-white px-3 text-xs';

    return (
        <AdminLayout title="Rekap & Presensi">
            <div className="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 className="text-xl font-extrabold text-[#1E1915]">
                        Rekapitulasi Kehadiran
                    </h1>
                    <p className="mt-1 text-xs text-[#7C7269]">
                        Verifikasi kehadiran anggota, kirim pengingat, atau
                        catat presensi manual.
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    <a
                        href={exportUrl('xlsx')}
                        className="flex items-center gap-1.5 rounded-xl bg-[#0FA958] px-3.5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-[#0c924b]"
                    >
                        <Download className="h-3.5 w-3.5" />
                        <span>Export Excel</span>
                    </a>
                    <a
                        href={exportUrl('pdf')}
                        className="flex items-center gap-1.5 rounded-xl bg-[#5C3806] px-3.5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-[#472b04]"
                    >
                        <Download className="h-3.5 w-3.5" />
                        <span>Export PDF</span>
                    </a>
                </div>
            </div>

            <div className="mb-5 grid grid-cols-1 gap-3 rounded-3xl border border-[#EFEAE4] bg-white p-4 shadow-2xs sm:grid-cols-4">
                <div>
                    <label className="mb-1 block text-[11px] font-bold text-[#7C7269]">
                        Agenda
                    </label>
                    <select
                        value={filters.agenda_id ?? ''}
                        onChange={(e) =>
                            applyFilter({
                                agenda_id: e.target.value,
                                session_id: null,
                            })
                        }
                        className={fieldClass}
                    >
                        {agendas.map((a) => (
                            <option key={a.id} value={a.id}>
                                {a.title}
                            </option>
                        ))}
                    </select>
                </div>

                {tab !== 'range' ? (
                    <div>
                        <label className="mb-1 block text-[11px] font-bold text-[#7C7269]">
                            Tanggal Sesi
                        </label>
                        <select
                            value={filters.session_id ?? ''}
                            onChange={(e) =>
                                applyFilter({ session_id: e.target.value })
                            }
                            className={fieldClass}
                        >
                            {sessions.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.session_date.slice(0, 10)} ({s.status})
                                </option>
                            ))}
                        </select>
                    </div>
                ) : (
                    <>
                        <div>
                            <label className="mb-1 block text-[11px] font-bold text-[#7C7269]">
                                Dari Tanggal
                            </label>
                            <input
                                type="date"
                                value={filters.from_date ?? ''}
                                onChange={(e) =>
                                    applyFilter({ from_date: e.target.value })
                                }
                                className={fieldClass}
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-[11px] font-bold text-[#7C7269]">
                                Sampai Tanggal
                            </label>
                            <input
                                type="date"
                                value={filters.to_date ?? ''}
                                onChange={(e) =>
                                    applyFilter({ to_date: e.target.value })
                                }
                                className={fieldClass}
                            />
                        </div>
                    </>
                )}

                <div>
                    <label className="mb-1 block text-[11px] font-bold text-[#7C7269]">
                        Filter Divisi
                    </label>
                    <select
                        value={filters.division_id ?? ''}
                        onChange={(e) =>
                            applyFilter({ division_id: e.target.value || null })
                        }
                        className={fieldClass}
                    >
                        <option value="">Semua Divisi</option>
                        {divisions.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="inline-flex gap-1.5 rounded-2xl border border-[#EAE4DC] bg-white p-1.5">
                    {(
                        [
                            [
                                'session',
                                `Rekap Sesi (${presentCount}/${sessionRows.length} Hadir)`,
                            ],
                            ['pending', `Belum Hadir (${pendingRows.length})`],
                            ['range', 'Rekap Rentang & Skor'],
                        ] as const
                    ).map(([key, label]) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setTab(key)}
                            className={`rounded-xl px-4 py-2 text-xs font-bold transition ${
                                tab === key
                                    ? 'bg-[#F39C12] text-white'
                                    : 'text-[#7C7269]'
                            }`}
                        >
                            {label}
                        </button>
                    ))}
                </div>

                {activeSession && pendingRows.length > 0 && tab !== 'range' && (
                    <button
                        type="button"
                        onClick={() =>
                            router.post(
                                `/admin/sessions/${activeSession.id}/remind-pending`,
                                {},
                                { preserveScroll: true },
                            )
                        }
                        className="flex items-center gap-1.5 rounded-xl bg-[#5B4DFF] px-4 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-[#4838eb]"
                    >
                        <Bell className="h-3.5 w-3.5" />
                        <span>
                            Kirim Pengingat ke {pendingRows.length} Anggota
                        </span>
                    </button>
                )}
            </div>

            <div className="overflow-hidden rounded-3xl border border-[#EFEAE4] bg-white shadow-2xs">
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr className="border-b border-[#EAE4DC] bg-[#F6F1EB] text-[#5C3806]">
                                <th className="p-3.5 font-bold">
                                    Nama Anggota
                                </th>
                                <th className="p-3.5 font-bold">Divisi</th>
                                {tab === 'range' ? (
                                    <>
                                        <th className="p-3.5 text-center font-bold">
                                            Hadir / Total
                                        </th>
                                        <th className="p-3.5 text-center font-bold">
                                            Persentase
                                        </th>
                                        <th className="p-3.5 text-center font-bold">
                                            Skor
                                        </th>
                                    </>
                                ) : (
                                    <>
                                        <th className="p-3.5 font-bold">
                                            Status
                                        </th>
                                        <th className="p-3.5 font-bold">
                                            Waktu & Jarak
                                        </th>
                                        <th className="p-3.5 font-bold">
                                            Sumber / Percobaan
                                        </th>
                                        <th className="p-3.5 text-right font-bold">
                                            Aksi
                                        </th>
                                    </>
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#F2EDE7]">
                            {tab === 'range'
                                ? rangeRows.map((row) => (
                                      <tr
                                          key={row.user_id}
                                          className="hover:bg-[#FAF7F2]"
                                      >
                                          <td className="p-3.5 font-bold text-[#1E1915]">
                                              {row.name}
                                          </td>
                                          <td className="p-3.5 text-[#7C7269]">
                                              {row.division}
                                          </td>
                                          <td className="p-3.5 text-center font-semibold">
                                              {row.attended_count} /{' '}
                                              {row.total_sessions}
                                          </td>
                                          <td className="p-3.5 text-center font-bold text-[#F39C12]">
                                              {row.percentage}%
                                          </td>
                                          <td className="p-3.5 text-center">
                                              <span className="rounded-lg bg-[#EEF0FF] px-2.5 py-1 font-extrabold text-[#5B4DFF]">
                                                  {row.score.toFixed(1)}
                                              </span>
                                          </td>
                                      </tr>
                                  ))
                                : rows.map((row) => (
                                      <tr
                                          key={row.user_id}
                                          className="hover:bg-[#FAF7F2]"
                                      >
                                          <td className="p-3.5 font-bold text-[#1E1915]">
                                              {row.name}
                                          </td>
                                          <td className="p-3.5 text-[#7C7269]">
                                              {row.division}
                                          </td>
                                          <td className="p-3.5">
                                              <span
                                                  className={`rounded-lg px-2.5 py-1 font-bold ${
                                                      row.status === 'hadir'
                                                          ? 'bg-[#E8F8F0] text-[#0FA958]'
                                                          : 'bg-[#FDECEE] text-[#EF4444]'
                                                  }`}
                                              >
                                                  {row.status === 'hadir'
                                                      ? 'Hadir'
                                                      : 'Belum hadir'}
                                              </span>
                                          </td>
                                          <td className="p-3.5 text-[#7C7269]">
                                              {row.status === 'hadir' ? (
                                                  <>
                                                      <strong className="text-[#1E1915]">
                                                          {row.checked_at}
                                                      </strong>{' '}
                                                      (
                                                      {row.distance_m !== null
                                                          ? `${row.distance_m}m`
                                                          : 'Manual'}
                                                      )
                                                  </>
                                              ) : (
                                                  '-'
                                              )}
                                          </td>
                                          <td className="p-3.5">
                                              {row.status === 'hadir' ? (
                                                  <div>
                                                      <span className="text-[11px] font-semibold uppercase">
                                                          {row.source}
                                                      </span>
                                                      {row.manual_reason && (
                                                          <p className="text-[11px] text-[#7C7269]">
                                                              “
                                                              {
                                                                  row.manual_reason
                                                              }
                                                              ” (
                                                              {
                                                                  row.created_by_name
                                                              }
                                                              )
                                                          </p>
                                                      )}
                                                  </div>
                                              ) : (
                                                  <div className="flex items-center gap-1.5">
                                                      <span>
                                                          Gagal:{' '}
                                                          {row.failed_attempts}
                                                          /3
                                                      </span>
                                                      {row.is_locked && (
                                                          <span className="inline-flex items-center gap-1 rounded bg-[#FDECEE] px-2 py-0.5 text-[10px] font-bold text-[#EF4444]">
                                                              <Lock className="h-3 w-3" />{' '}
                                                              Terkunci
                                                          </span>
                                                      )}
                                                  </div>
                                              )}
                                          </td>
                                          <td className="p-3.5 text-right">
                                              {row.status === 'belum_hadir' && (
                                                  <button
                                                      type="button"
                                                      onClick={() =>
                                                          openManualModal(row)
                                                      }
                                                      className="inline-flex items-center gap-1 rounded-xl bg-[#FFF4E5] px-3 py-1.5 font-bold text-[#5C3806] transition hover:bg-[#F39C12] hover:text-white"
                                                  >
                                                      <PlusCircle className="h-3.5 w-3.5" />
                                                      <span>
                                                          Presensi Manual
                                                      </span>
                                                  </button>
                                              )}
                                          </td>
                                      </tr>
                                  ))}
                        </tbody>
                    </table>
                </div>

                {rows.length === 0 && (
                    <p className="py-8 text-center text-xs text-[#7C7269]">
                        Tidak ada data untuk filter ini.
                    </p>
                )}
            </div>

            {manualTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-4 backdrop-blur-xs">
                    <form
                        onSubmit={submitManualAttendance}
                        className="w-full max-w-md space-y-4 rounded-3xl bg-white p-6 shadow-xl"
                    >
                        <h3 className="text-base font-extrabold text-[#1E1915]">
                            Catat Presensi Manual — {manualTarget.name}
                        </h3>
                        <p className="text-xs text-[#7C7269]">
                            Tindakan ini mencatat anggota sebagai{' '}
                            <strong>Hadir</strong> dan tersimpan permanen di Log
                            Audit beserta nama Anda.
                        </p>

                        <div>
                            <label className="mb-1.5 block text-xs font-bold text-[#1E1915]">
                                Alasan (min. 10 karakter)
                            </label>
                            <textarea
                                rows={3}
                                required
                                minLength={10}
                                value={manualForm.data.reason}
                                onChange={(e) =>
                                    manualForm.setData('reason', e.target.value)
                                }
                                placeholder="Contoh: Hadir di lokasi namun GPS HP terkendala akurasi di dalam gedung."
                                className="w-full rounded-xl border border-[#E2DAD0] p-3 text-xs"
                            />
                            {manualForm.errors.reason && (
                                <p className="mt-1 text-[11px] font-medium text-[#EF4444]">
                                    {manualForm.errors.reason}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                onClick={() => setManualTarget(null)}
                                className="rounded-xl bg-[#F6F1EB] px-4 py-2.5 text-xs font-bold text-[#7C7269]"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={
                                    manualForm.processing ||
                                    manualForm.data.reason.trim().length < 10
                                }
                                className="rounded-xl bg-[#F39C12] px-4 py-2.5 text-xs font-bold text-white disabled:opacity-50"
                            >
                                Simpan Presensi
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </AdminLayout>
    );
}
