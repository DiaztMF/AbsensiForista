import { CheckCircle2, ChevronRight, Hand, Loader2, Lock } from 'lucide-react';
import { useRef, useState } from 'react';

interface CheckInTriggerProps {
    disabled: boolean;
    disabledLabel?: string;
    isLocked: boolean;
    hasCheckedIn: boolean;
    isProcessing: boolean;
    onTriggerCheckIn: () => void;
}

const KNOB = 44; // px, tinggi & lebar knob
const GUTTER = 6; // px, jarak knob dari tepi track

export default function CheckInTrigger({
    disabled,
    disabledLabel,
    isLocked,
    hasCheckedIn,
    isProcessing,
    onTriggerCheckIn,
}: CheckInTriggerProps) {
    const trackRef = useRef<HTMLDivElement>(null);
    const [dragRatio, setDragRatio] = useState(0);
    const [isDragging, setIsDragging] = useState(false);

    if (hasCheckedIn) {
        return (
            <div className="flex flex-col items-center py-3">
                <div className="flex h-28 w-28 flex-col items-center justify-center rounded-full border-4 border-[#0FA958]/20 bg-[#E8F8F0] text-[#0FA958]">
                    <CheckCircle2 className="mb-1 h-8 w-8" />
                    <span className="text-xs font-bold">Sudah Hadir</span>
                </div>
            </div>
        );
    }

    if (isLocked) {
        return (
            <div className="w-full rounded-2xl border border-[#EF4444]/30 bg-[#FDECEE] p-4 text-center">
                <Lock className="mx-auto mb-1.5 h-6 w-6 text-[#EF4444]" />
                <p className="text-xs font-bold text-[#EF4444]">
                    Percobaan Presensi Terkunci (3/3)
                </p>
                <p className="mt-0.5 text-[11px] text-[#7C7269]">
                    Silakan hubungi Admin FORISTA untuk pencatatan presensi
                    manual.
                </p>
            </div>
        );
    }

    const maxTravel = () =>
        Math.max(1, (trackRef.current?.clientWidth ?? 0) - KNOB - GUTTER * 2);

    const handlePointerDown = (e: React.PointerEvent<HTMLDivElement>) => {
        if (disabled || isProcessing) {
            return;
        }

        e.currentTarget.setPointerCapture(e.pointerId);
        setIsDragging(true);
    };

    const handlePointerMove = (e: React.PointerEvent<HTMLDivElement>) => {
        const track = trackRef.current;
        if (!isDragging || !track) {
            return;
        }

        const rect = track.getBoundingClientRect();
        const x = e.clientX - rect.left - KNOB / 2;
        setDragRatio(Math.min(1, Math.max(0, x / maxTravel())));
    };

    const release = () => {
        if (!isDragging) {
            return;
        }

        setIsDragging(false);

        // F4 PRD: melewati 75% lintasan memulai pelacakan; kurang dari itu kembali ke awal
        if (dragRatio >= 0.75 && !disabled && !isProcessing) {
            setDragRatio(1);
            onTriggerCheckIn();
            setTimeout(() => setDragRatio(0), 1000);
        } else {
            setDragRatio(0);
        }
    };

    return (
        <div className="flex flex-col items-center gap-3">
            <button
                type="button"
                disabled={disabled || isProcessing}
                onClick={onTriggerCheckIn}
                className={`flex h-28 w-28 flex-col items-center justify-center rounded-full shadow-md transition-transform active:scale-95 ${
                    disabled
                        ? 'cursor-not-allowed border-4 border-[#EFECE6] bg-[#E5E0DA] text-[#8C827A]'
                        : 'border-6 border-[#E2F5EA] bg-[#0FA958] text-white hover:bg-[#0c924b]'
                }`}
                aria-label="Tekan untuk melakukan check in"
            >
                {isProcessing ? (
                    <>
                        <Loader2 className="mb-1 h-7 w-7 animate-spin" />
                        <span className="text-[11px] font-semibold">
                            Melacak...
                        </span>
                    </>
                ) : (
                    <>
                        <Hand className="mb-1 h-7 w-7" />
                        <span className="text-xs font-bold">
                            {disabled ? (disabledLabel ?? 'Tutup') : 'Check In'}
                        </span>
                    </>
                )}
            </button>

            <div
                ref={trackRef}
                className={`relative flex h-14 w-full touch-none items-center overflow-hidden rounded-2xl border p-1 select-none ${
                    disabled
                        ? 'border-[#E5E0DA] bg-[#EFECE6]'
                        : 'border-[#F39C12]/30 bg-[#FFF8EB]'
                }`}
            >
                <p className="pointer-events-none w-full text-center text-xs font-semibold text-[#7C7269]">
                    {disabled
                        ? disabledLabel
                        : isProcessing
                          ? 'Memverifikasi koordinat GPS...'
                          : 'Geser ke kanan untuk check in (75%)'}
                </p>

                {!disabled && (
                    <div
                        role="slider"
                        aria-label="Geser untuk check in"
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={Math.round(dragRatio * 100)}
                        tabIndex={0}
                        onPointerDown={handlePointerDown}
                        onPointerMove={handlePointerMove}
                        onPointerUp={release}
                        onPointerCancel={release}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                onTriggerCheckIn();
                            }
                        }}
                        style={{
                            transform: `translateX(${dragRatio * maxTravel()}px)`,
                            transition: isDragging
                                ? 'none'
                                : 'transform 0.25s ease',
                        }}
                        className="absolute left-1.5 flex h-11 w-11 cursor-grab items-center justify-center rounded-xl bg-[#F39C12] text-white shadow-sm active:cursor-grabbing"
                    >
                        <ChevronRight className="h-5 w-5" />
                    </div>
                )}
            </div>
        </div>
    );
}
