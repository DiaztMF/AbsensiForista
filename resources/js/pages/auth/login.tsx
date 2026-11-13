import AppLogo from "@/components/app-logo";
import { Head, useForm } from "@inertiajs/react";
import { Eye, EyeOff, Loader2, Lock, MapPinCheck, User } from "lucide-react";
import { useState } from "react";

export default function Login() {
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        username: "",
        password: "",
        remember: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post("/login", { onFinish: () => reset("password") });
    };

    const fieldClass =
        "h-11 w-full rounded-xl border border-[#E2DAD0] bg-white pr-4 pl-10 text-sm text-[#1E1915] transition placeholder:text-[#A39A91] focus:border-[#F39C12] focus:ring-2 focus:ring-[#F39C12]/20 focus:outline-none";

    return (
        <div className="flex min-h-dvh w-full flex-col bg-[#F6F1EB] text-[#1E1915] lg:flex-row">
            <Head title="Masuk — Presensi FORISTA" />

            <div className="relative hidden overflow-hidden bg-[#5C3806] select-none lg:flex lg:w-1/2">
                <img
                    src="/images/login_forista.jpg"
                    alt="Forum OSIS Surakarta"
                    className="absolute inset-0 h-full w-full object-cover object-center"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-[#241502]/90 via-[#5C3806]/35 to-transparent" />

                <div className="relative z-10 mt-auto max-w-xl p-12 text-white">
                    <blockquote className="text-xl leading-relaxed font-semibold tracking-tight">
                        “Kepemimpinan lahir dari kehadiran, konsistensi, dan tanggung jawab nyata di
                        setiap pertemuan.”
                    </blockquote>
                    <p className="mt-3 text-sm font-medium text-white/80">
                        Forum OSIS Surakarta (FORISTA)
                    </p>
                </div>
            </div>

            <div className="rounded-b-[32px] bg-[#5C3806] px-6 pt-10 pb-8 text-white shadow-sm lg:hidden">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#F39C12] text-white shadow-xs">
                        <AppLogo />
                    </div>
                    <div>
                        <p className="text-base leading-none font-bold tracking-tight">FORISTA</p>
                        <p className="mt-1 text-xs text-white/75">Forum OSIS Surakarta</p>
                    </div>
                </div>
            </div>

            <div className="flex flex-1 flex-col justify-between bg-[#F6F1EB] px-6 py-8 lg:px-14 lg:py-10">
                <div className="hidden items-center justify-between lg:flex">
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-9 w-9 items-center justify-center">
                            <AppLogo />
                        </div>
                        <span className="text-base font-extrabold tracking-tight text-[#1E1915]">
                            FORISTA <span className="font-medium text-[#7C7269]">Attendance</span>
                        </span>
                    </div>

                </div>

                <div className="mx-auto my-auto w-full max-w-sm py-6">
                    <div className="mb-7">
                        <h1 className="text-2xl font-extrabold tracking-tight text-[#1E1915]">
                            Masuk ke Akun
                        </h1>
                        <p className="mt-1.5 text-sm text-[#7C7269]">
                            Masukkan username dan kata sandi untuk mengakses presensi rapat.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label
                                htmlFor="username"
                                className="mb-1.5 block text-xs font-bold text-[#1E1915]"
                            >
                                Username
                            </label>
                            <div className="relative">
                                <User className="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#7C7269]" />
                                <input
                                    id="username"
                                    name="username"
                                    type="text"
                                    autoComplete="username"
                                    autoCapitalize="none"
                                    autoFocus
                                    value={data.username}
                                    onChange={(e) => setData("username", e.target.value)}
                                    placeholder="Masukkan username Anda"
                                    className={fieldClass}
                                />
                            </div>
                            {errors.username && (
                                <p className="mt-1.5 text-xs font-medium text-[#EF4444]">
                                    {errors.username}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="password"
                                className="mb-1.5 block text-xs font-bold text-[#1E1915]"
                            >
                                Kata Sandi
                            </label>
                            <div className="relative">
                                <Lock className="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#7C7269]" />
                                <input
                                    id="password"
                                    name="password"
                                    type={showPassword ? "text" : "password"}
                                    autoComplete="current-password"
                                    value={data.password}
                                    onChange={(e) => setData("password", e.target.value)}
                                    placeholder="••••••••"
                                    className={`${fieldClass} pr-11`}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword((prev) => !prev)}
                                    className="absolute top-1/2 right-3 -translate-y-1/2 p-1 text-[#7C7269] hover:text-[#1E1915]"
                                    aria-label={
                                        showPassword ? "Sembunyikan kata sandi" : "Lihat kata sandi"
                                    }
                                >
                                    {showPassword ? (
                                        <EyeOff className="h-4 w-4" />
                                    ) : (
                                        <Eye className="h-4 w-4" />
                                    )}
                                </button>
                            </div>
                            {errors.password && (
                                <p className="mt-1.5 text-xs font-medium text-[#EF4444]">
                                    {errors.password}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center justify-between pt-0.5">
                            <label className="inline-flex cursor-pointer items-center gap-2 select-none">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData("remember", e.target.checked)}
                                    className="h-4 w-4 rounded border-[#D6CEC4] text-[#F39C12] focus:ring-[#F39C12]"
                                />
                                <span className="text-xs font-medium text-[#7C7269]">
                                    Tetap masuk di perangkat ini
                                </span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-2 flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-[#F39C12] text-sm font-bold text-white shadow-xs transition hover:bg-[#DF8C09] disabled:opacity-60"
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    <span>Memverifikasi...</span>
                                </>
                            ) : (
                                <span>Masuk</span>
                            )}
                        </button>
                    </form>

                    <div className="mt-6 rounded-2xl border border-[#E6DFD6] bg-white/80 p-3.5 text-center">
                        <p className="text-xs leading-relaxed text-[#7C7269]">
                            Lupa kata sandi atau belum memiliki akun?{" "}
                            <br></br>
                            <span className="font-semibold text-[#5C3806]">
                                Hubungi Admin
                            </span>{" "}
                            untuk pembuatan akun atau reset sandi.
                        </p>
                    </div>
                </div>

                <footer className="mx-auto max-w-sm text-center text-[11px] leading-relaxed text-[#7C7269]">
                    Akses lokasi GPS hanya digunakan sesaat ketika Anda melakukan presensi rapat dan
                    koordinat mentah tidak disimpan.
                </footer>
            </div>
        </div>
    );
}
