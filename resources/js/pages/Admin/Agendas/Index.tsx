import { router, useForm } from "@inertiajs/react";
import { CalendarClock, MapPin, Search, XCircle } from "lucide-react";
import L from "leaflet";
import { useState } from "react";
import { Circle, MapContainer, Marker, TileLayer, useMapEvents } from "react-leaflet";
import AdminLayout from "@/layouts/admin-layout";

const weekdays = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];

const pinIcon = L.divIcon({
    className: "custom-agenda-pin",
    html: `<div style="width:32px;height:32px;border-radius:9999px;background:rgba(91,77,255,.2);border:2px solid #5B4DFF;display:flex;align-items:center;justify-content:center"><div style="width:10px;height:10px;border-radius:9999px;background:#5B4DFF"></div></div>`,
    iconSize: [32, 32],
    iconAnchor: [16, 16],
});

interface Session {
    id: number;
    session_date: string;
    window_opens_at: string;
    window_closes_at: string;
    status: string;
}

interface Agenda {
    id: number;
    title: string;
    location_name: string;
    radius_m: number;
    window_minutes: number;
    weekday: number;
    start_time: string;
    end_time: string;
    sessions: Session[];
}

interface Defaults {
    default_radius_m: number;
    default_window_minutes: number;
}

interface AgendasProps {
    agendas: Agenda[];
    defaults: Defaults;
}

interface GeocodeResult {
    display_name: string;
    latitude: number;
    longitude: number;
}

function LocationPickerMap({
    lat,
    lng,
    radiusM,
    onPick,
}: {
    lat: number;
    lng: number;
    radiusM: number;
    onPick: (lat: number, lng: number) => void;
}) {
    function ClickHandler() {
        useMapEvents({
            click(e) {
                onPick(Number(e.latlng.lat.toFixed(6)), Number(e.latlng.lng.toFixed(6)));
            },
        });

        return null;
    }

    return (
        <div className="h-64 w-full overflow-hidden rounded-2xl border border-[#E2DAD0]">
            <MapContainer
                center={[lat, lng]}
                zoom={17}
                scrollWheelZoom={false}
                className="h-full w-full"
            >
                <TileLayer
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>'
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                />
                <ClickHandler />
                <Circle
                    center={[lat, lng]}
                    radius={radiusM}
                    pathOptions={{
                        color: "#5B4DFF",
                        fillColor: "#5B4DFF",
                        fillOpacity: 0.2,
                    }}
                />
                <Marker position={[lat, lng]} icon={pinIcon} />
            </MapContainer>
        </div>
    );
}

const clock = (iso: string) =>
    new Date(iso).toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
    });

export default function AdminAgendasIndex({ agendas, defaults }: AgendasProps) {
    const [searchQuery, setSearchQuery] = useState("");
    const [searchResults, setSearchResults] = useState<GeocodeResult[]>([]);
    const [searching, setSearching] = useState(false);
    const [shiftSession, setShiftSession] = useState<Session | null>(null);

    const form = useForm({
        title: "",
        location_name: "",
        address: "",
        latitude: -7.569548,
        longitude: 110.828316,
        radius_m: defaults?.default_radius_m ?? 20,
        weekday: 6,
        start_time: "13:00",
        end_time: "15:00",
        window_minutes: defaults?.default_window_minutes ?? 15,
        starts_on: new Date().toISOString().slice(0, 10),
        ends_on: "",
        is_active: true,
    });

    const shiftForm = useForm({ window_opens_at: "", window_closes_at: "" });

    const handleSearchAddress = async () => {
        if (searchQuery.trim().length < 3) {
            return;
        }

        setSearching(true);

        try {
            const res = await fetch(`/admin/geocode?q=${encodeURIComponent(searchQuery)}`, {
                headers: { Accept: "application/json" },
            });
            const data = await res.json();
            setSearchResults(data.results ?? []);
        } finally {
            setSearching(false);
        }
    };

    const submitAgenda = (e: React.FormEvent) => {
        e.preventDefault();
        form.post("/admin/agendas", {
            preserveScroll: true,
            onSuccess: () => form.reset("title", "location_name", "address"),
        });
    };

    const submitShiftWindow = (e: React.FormEvent) => {
        e.preventDefault();
        if (!shiftSession) {
            return;
        }

        shiftForm.patch(`/admin/sessions/${shiftSession.id}/shift-window`, {
            preserveScroll: true,
            onSuccess: () => setShiftSession(null),
        });
    };

    const field = (extra = "") => `w-full rounded-xl border border-[#E2DAD0] px-3 text-xs ${extra}`;

    return (
        <AdminLayout title="Agenda & Sesi">
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <form
                    onSubmit={submitAgenda}
                    className="h-fit space-y-4 rounded-3xl border border-[#EFEAE4] bg-white p-6 shadow-2xs lg:col-span-5"
                >
                    <h2 className="text-base font-extrabold text-[#1E1915]">
                        Buat Agenda Mingguan Baru
                    </h2>

                    <div>
                        <label className="mb-1 block text-xs font-bold">Nama Agenda</label>
                        <input
                            type="text"
                            required
                            value={form.data.title}
                            onChange={(e) => form.setData("title", e.target.value)}
                            placeholder="Rapat Pleno Mingguan FORISTA"
                            className={field("h-10")}
                        />
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-bold">Nama Tempat / Venue</label>
                        <input
                            type="text"
                            required
                            value={form.data.location_name}
                            onChange={(e) => form.setData("location_name", e.target.value)}
                            placeholder="Gedung Pemuda / Balai Kota Surakarta"
                            className={field("h-10")}
                        />
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-bold">
                            Cari Alamat (atau klik langsung di peta)
                        </label>
                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === "Enter") {
                                        e.preventDefault();
                                        void handleSearchAddress();
                                    }
                                }}
                                placeholder="Ketik nama tempat di Surakarta..."
                                className={field("h-10 flex-1")}
                            />
                            <button
                                type="button"
                                onClick={handleSearchAddress}
                                disabled={searching}
                                className="flex h-10 items-center gap-1 rounded-xl bg-[#5C3806] px-3.5 text-xs font-bold text-white disabled:opacity-60"
                            >
                                <Search className="h-3.5 w-3.5" />
                                <span>{searching ? "..." : "Cari"}</span>
                            </button>
                        </div>

                        {searchResults.length > 0 && (
                            <div className="mt-2 max-h-36 divide-y divide-[#EAE4DC] overflow-y-auto rounded-xl border border-[#E2DAD0] bg-[#FAF7F2]">
                                {searchResults.map((item) => (
                                    <button
                                        key={item.display_name}
                                        type="button"
                                        onClick={() => {
                                            form.setData({
                                                ...form.data,
                                                address: item.display_name,
                                                latitude: item.latitude,
                                                longitude: item.longitude,
                                            });
                                            setSearchResults([]);
                                        }}
                                        className="w-full p-2.5 text-left text-[11px] transition hover:bg-white"
                                    >
                                        {item.display_name}
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    <LocationPickerMap
                        lat={Number(form.data.latitude)}
                        lng={Number(form.data.longitude)}
                        radiusM={Number(form.data.radius_m)}
                        onPick={(lat, lng) =>
                            form.setData({ ...form.data, latitude: lat, longitude: lng })
                        }
                    />

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="mb-1 block text-[11px] font-bold">Latitude</label>
                            <input
                                type="number"
                                step="any"
                                required
                                value={form.data.latitude}
                                onChange={(e) =>
                                    form.setData("latitude", parseFloat(e.target.value) || 0)
                                }
                                className={field("h-9")}
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-[11px] font-bold">Longitude</label>
                            <input
                                type="number"
                                step="any"
                                required
                                value={form.data.longitude}
                                onChange={(e) =>
                                    form.setData("longitude", parseFloat(e.target.value) || 0)
                                }
                                className={field("h-9")}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                        <div>
                            <label className="mb-1 block text-[11px] font-bold">Hari Rapat</label>
                            <select
                                value={form.data.weekday}
                                onChange={(e) => form.setData("weekday", Number(e.target.value))}
                                className={field("h-9 bg-white")}
                            >
                                {weekdays.map((day, idx) => (
                                    <option key={day} value={idx}>
                                        {day}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="mb-1 block text-[11px] font-bold">Mulai</label>
                            <input
                                type="time"
                                required
                                value={form.data.start_time}
                                onChange={(e) => form.setData("start_time", e.target.value)}
                                className={field("h-9")}
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-[11px] font-bold">Selesai</label>
                            <input
                                type="time"
                                required
                                value={form.data.end_time}
                                onChange={(e) => form.setData("end_time", e.target.value)}
                                className={field("h-9")}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="mb-1 block text-[11px] font-bold">
                                Radius (5–100 m)
                            </label>
                            <input
                                type="number"
                                min={5}
                                max={100}
                                required
                                value={form.data.radius_m}
                                onChange={(e) => form.setData("radius_m", Number(e.target.value))}
                                className={field("h-9")}
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-[11px] font-bold">
                                Window (5–120 mnt)
                            </label>
                            <input
                                type="number"
                                min={5}
                                max={120}
                                required
                                value={form.data.window_minutes}
                                onChange={(e) =>
                                    form.setData("window_minutes", Number(e.target.value))
                                }
                                className={field("h-9")}
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="h-11 w-full rounded-xl bg-[#F39C12] text-xs font-bold text-white shadow-xs transition hover:bg-[#DF8C09] disabled:opacity-60"
                    >
                        Simpan Agenda & Generate Sesi 4 Minggu
                    </button>
                </form>

                <div className="space-y-4 lg:col-span-7">
                    {agendas.map((agenda) => (
                        <div
                            key={agenda.id}
                            className="rounded-3xl border border-[#EFEAE4] bg-white p-5 shadow-2xs"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h3 className="text-base font-extrabold text-[#1E1915]">
                                        {agenda.title}
                                    </h3>
                                    <p className="mt-0.5 flex items-center gap-1 text-xs text-[#7C7269]">
                                        <MapPin className="h-3.5 w-3.5 text-[#5B4DFF]" />
                                        {agenda.location_name} • Radius {agenda.radius_m}m • Window{" "}
                                        {agenda.window_minutes} menit
                                    </p>
                                </div>
                                <span className="rounded-full bg-[#EEF0FF] px-3 py-1 text-xs font-bold text-[#5B4DFF]">
                                    {weekdays[agenda.weekday]} ({agenda.start_time.slice(0, 5)} -{" "}
                                    {agenda.end_time.slice(0, 5)})
                                </span>
                            </div>

                            <div className="mt-4 border-t border-[#F2EDE7] pt-4">
                                <p className="mb-2.5 text-xs font-bold text-[#7C7269]">
                                    Sesi Terjadwal (4 Minggu ke Depan)
                                </p>
                                <div className="space-y-2">
                                    {agenda.sessions.map((session) => (
                                        <div
                                            key={session.id}
                                            className="flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-[#F6F1EB] p-3 text-xs"
                                        >
                                            <div>
                                                <span className="font-bold text-[#1E1915]">
                                                    {session.session_date.slice(0, 10)}
                                                </span>
                                                <span className="ml-2 text-[#7C7269]">
                                                    Window: {clock(session.window_opens_at)} -{" "}
                                                    {clock(session.window_closes_at)} WIB
                                                </span>
                                            </div>

                                            {session.status === "cancelled" ? (
                                                <span className="rounded-lg bg-[#FDECEE] px-2.5 py-1 font-bold text-[#EF4444]">
                                                    Dibatalkan
                                                </span>
                                            ) : (
                                                <div className="flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setShiftSession(session);
                                                            shiftForm.setData({
                                                                window_opens_at:
                                                                    session.window_opens_at.slice(
                                                                        0,
                                                                        16,
                                                                    ),
                                                                window_closes_at:
                                                                    session.window_closes_at.slice(
                                                                        0,
                                                                        16,
                                                                    ),
                                                            });
                                                        }}
                                                        className="flex items-center gap-1 rounded-lg border border-[#E2DAD0] bg-white px-2.5 py-1.5 font-bold text-[#5C3806]"
                                                    >
                                                        <CalendarClock className="h-3.5 w-3.5" />
                                                        <span>Geser Window</span>
                                                    </button>

                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            router.post(
                                                                `/admin/sessions/${session.id}/cancel`,
                                                                {},
                                                                { preserveScroll: true },
                                                            )
                                                        }
                                                        className="flex items-center gap-1 rounded-lg bg-[#FDECEE] px-2.5 py-1.5 font-bold text-[#EF4444]"
                                                    >
                                                        <XCircle className="h-3.5 w-3.5" />
                                                        <span>Batalkan</span>
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ))}

                    {agendas.length === 0 && (
                        <div className="rounded-3xl border border-[#EFEAE4] bg-white p-8 text-center text-xs text-[#7C7269]">
                            Belum ada agenda. Buat agenda mingguan pertama Anda.
                        </div>
                    )}
                </div>
            </div>

            {shiftSession && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-4 backdrop-blur-xs">
                    <form
                        onSubmit={submitShiftWindow}
                        className="w-full max-w-sm space-y-4 rounded-3xl bg-white p-6 shadow-xl"
                    >
                        <h3 className="text-base font-extrabold text-[#1E1915]">
                            Geser Waktu Presensi Sesi
                        </h3>
                        <p className="text-xs text-[#7C7269]">
                            Hanya berlaku untuk sesi tanggal{" "}
                            <strong>{shiftSession.session_date.slice(0, 10)}</strong> dan
                            menjadwalkan ulang pengingat.
                        </p>

                        <div>
                            <label className="mb-1 block text-xs font-bold">Waktu Buka Baru</label>
                            <input
                                type="datetime-local"
                                required
                                value={shiftForm.data.window_opens_at}
                                onChange={(e) =>
                                    shiftForm.setData("window_opens_at", e.target.value)
                                }
                                className={field("h-10")}
                            />
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-bold">Waktu Tutup Baru</label>
                            <input
                                type="datetime-local"
                                required
                                value={shiftForm.data.window_closes_at}
                                onChange={(e) =>
                                    shiftForm.setData("window_closes_at", e.target.value)
                                }
                                className={field("h-10")}
                            />
                        </div>

                        <div className="flex justify-end gap-2 pt-2">
                            <button
                                type="button"
                                onClick={() => setShiftSession(null)}
                                className="rounded-xl bg-[#F6F1EB] px-4 py-2 text-xs font-bold text-[#7C7269]"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={shiftForm.processing}
                                className="rounded-xl bg-[#F39C12] px-4 py-2 text-xs font-bold text-white disabled:opacity-60"
                            >
                                Simpan Jadwal Sesi
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </AdminLayout>
    );
}
