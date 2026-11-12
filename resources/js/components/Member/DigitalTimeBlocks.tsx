interface DigitalTimeBlocksProps {
    label: string;
    timeString: string;
    subLabel?: string;
}

export default function DigitalTimeBlocks({ label, timeString, subLabel }: DigitalTimeBlocksProps) {
    const [hours = "00", minutes = "00"] = timeString.split(":");

    return (
        <div className="text-center">
            <p className="mb-2.5 text-xs font-medium text-[#7C7269]">{label}</p>
            <div className="inline-flex items-center gap-2">
                <div className="flex h-14 w-16 items-center justify-center rounded-2xl bg-[#F4F3F8] text-2xl font-bold text-[#1E1915] shadow-xs">
                    {hours}
                </div>
                <span className="text-xl font-bold text-[#1E1915]">:</span>
                <div className="flex h-14 w-16 items-center justify-center rounded-2xl bg-[#F4F3F8] text-2xl font-bold text-[#1E1915] shadow-xs">
                    {minutes}
                </div>
                <span className="text-xl font-bold text-[#1E1915]">:</span>
                <div className="flex h-14 items-center justify-center rounded-2xl bg-[#F4F3F8] px-3.5 text-base font-bold text-[#5B4DFF] shadow-xs">
                    WIB
                </div>
            </div>
            {subLabel && <p className="mt-2.5 text-xs font-medium text-[#7C7269]">{subLabel}</p>}
        </div>
    );
}
