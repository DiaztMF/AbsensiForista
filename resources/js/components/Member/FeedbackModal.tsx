import { AlertTriangle, CheckCircle2, Sparkles } from "lucide-react";
import { useEffect, useRef } from "react";

interface FeedbackModalProps {
    open: boolean;
    variant: "welcome" | "success" | "error";
    title: string;
    message: string;
    autoCloseMs?: number;
    onClose: () => void;
}

export default function FeedbackModal({
    open,
    variant,
    title,
    message,
    autoCloseMs,
    onClose,
}: FeedbackModalProps) {
    // Callback disimpan di ref agar timer tidak reset oleh render berkala (jam berjalan)
    const onCloseRef = useRef(onClose);
    onCloseRef.current = onClose;

    useEffect(() => {
        if (!open || !autoCloseMs) {
            return;
        }

        const timer = setTimeout(() => onCloseRef.current(), autoCloseMs);

        return () => clearTimeout(timer);
    }, [open, autoCloseMs]);

    if (!open) {
        return null;
    }

    return (
        <div
            onClick={onClose}
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-5 backdrop-blur-xs"
        >
            <div
                onClick={(e) => e.stopPropagation()}
                className="w-full max-w-xs animate-in rounded-3xl bg-white p-6 text-center shadow-xl duration-200 zoom-in-95 fade-in"
            >
                <div
                    className={`mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full ${
                        variant === "error"
                            ? "bg-[#FDECEE] text-[#EF4444]"
                            : "bg-[#5B4DFF] text-white shadow-lg shadow-[#5B4DFF]/25"
                    }`}
                >
                    {variant === "welcome" && <Sparkles className="h-9 w-9" />}
                    {variant === "success" && <CheckCircle2 className="h-9 w-9" />}
                    {variant === "error" && <AlertTriangle className="h-9 w-9" />}
                </div>

                <h3 className="text-lg font-bold text-[#1E1915]">{title}</h3>
                <p className="mt-2 text-xs leading-relaxed text-[#7C7269]">{message}</p>

                <button
                    type="button"
                    onClick={onClose}
                    className="mt-6 w-full rounded-xl bg-[#F39C12] py-3 text-sm font-bold text-white shadow-xs transition-colors hover:bg-[#DF8C09]"
                >
                    OK
                </button>
            </div>
        </div>
    );
}
