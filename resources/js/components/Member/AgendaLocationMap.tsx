import L from "leaflet";
import { Circle, MapContainer, Marker, TileLayer } from "react-leaflet";

const agendaPinIcon = L.divIcon({
    className: "custom-agenda-pin",
    html: `
        <div style="width:36px;height:36px;border-radius:9999px;background:rgba(91,77,255,.18);border:2px solid #5B4DFF;display:flex;align-items:center;justify-content:center">
            <div style="width:12px;height:12px;border-radius:9999px;background:#5B4DFF"></div>
        </div>
    `,
    iconSize: [36, 36],
    iconAnchor: [18, 18],
});

interface AgendaLocationMapProps {
    latitude: number;
    longitude: number;
    radiusM: number;
}

export default function AgendaLocationMap({
    latitude,
    longitude,
    radiusM,
}: AgendaLocationMapProps) {
    return (
        <div className="relative h-44 w-full overflow-hidden rounded-2xl border border-[#ECE6DF]">
            <MapContainer
                key={`${latitude},${longitude}`}
                center={[latitude, longitude]}
                zoom={18}
                scrollWheelZoom={false}
                zoomControl={false}
                className="h-full w-full"
            >
                <TileLayer
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>'
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                />
                <Circle
                    center={[latitude, longitude]}
                    radius={radiusM}
                    pathOptions={{
                        color: "#5B4DFF",
                        weight: 2,
                        fillColor: "#5B4DFF",
                        fillOpacity: 0.16,
                    }}
                />
                <Marker position={[latitude, longitude]} icon={agendaPinIcon} />
            </MapContainer>
            <div className="absolute bottom-2 left-2 z-20 rounded-lg bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-[#1E1915] shadow-xs backdrop-blur-xs">
                Radius Maks: {radiusM}m
            </div>
        </div>
    );
}
