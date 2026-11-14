import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CalendarCog,
    ClipboardList,
    FileClock,
    Hand,
    LayoutDashboard,
    LogOut,
    MapPinCheck,
    Settings,
    Users,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import type { Auth } from '@/types/auth';
import AppLogoIcon from '@/components/app-logo-icon';

type PageProps = {
    auth: Auth;
    errors: Record<string, string>;
    flash: { success?: string; toast?: { type: string; message: string } };
};

interface AdminLayoutProps extends PropsWithChildren {
    title: string;
}

const navItems = [
    { label: 'Ringkasan', href: '/admin', icon: LayoutDashboard },
    { label: 'Rekap', href: '/admin/recaps', icon: ClipboardList },
    { label: 'Agenda', href: '/admin/agendas', icon: CalendarCog },
    { label: 'Anggota', href: '/admin/members', icon: Users },
    { label: 'Log Audit', href: '/admin/logs', icon: FileClock },
    { label: 'Pengaturan', href: '/admin/settings', icon: Settings },
];

export default function AdminLayout({ title, children }: AdminLayoutProps) {
    const { url, props, flash } = usePage<PageProps>();
    const user = props.auth.user;
    const successMessage = flash?.success;
    const successText =
        typeof successMessage === 'string' ? successMessage : null;
    const errors = props.errors;

    const isActive = (href: string) =>
        href === '/admin' ? url === '/admin' : url.startsWith(href);

    return (
        <div className="flex min-h-dvh flex-col bg-[#F6F1EB] text-[#1E1915] lg:flex-row">
            <Head title={`${title} — Admin FORISTA`} />

            <aside className="hidden shrink-0 bg-[#5C3806] p-5 text-white lg:flex lg:w-64 lg:flex-col">
                <div className="flex items-center gap-3 border-b border-white/15 pb-6">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl text-white shadow-xs">
                        <AppLogoIcon className="h-8 w-8" />
                    </div>
                    <div>
                        <p className="text-base leading-none font-extrabold">
                            FORISTA
                        </p>
                        <p className="mt-1 text-xs text-white/70">
                            Panel Pengurus
                        </p>
                    </div>
                </div>

                <nav className="mt-6 flex-1 space-y-1.5">
                    {navItems.map((item) => {
                        const Icon = item.icon;

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition ${
                                    isActive(item.href)
                                        ? 'bg-[#F39C12] text-white shadow-xs'
                                        : 'text-white/80 hover:bg-white/10 hover:text-white'
                                }`}
                            >
                                <Icon className="h-4 w-4 shrink-0" />
                                <span>{item.label}</span>
                            </Link>
                        );
                    })}
                </nav>

                <div className="space-y-2 border-t border-white/15 pt-4">
                    <Link
                        href="/dashboard"
                        className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#0FA958] px-3.5 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-[#0c924b]"
                    >
                        <Hand className="h-4 w-4" />
                        <span>Mode Anggota (Check-In)</span>
                    </Link>

                    <div className="flex items-center justify-between px-1 pt-2">
                        <div className="min-w-0">
                            <p className="truncate text-xs font-bold">
                                {user.name}
                            </p>
                            <p className="truncate text-[11px] text-white/70">
                                @{user.username}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => router.post('/logout')}
                            className="rounded-lg p-2 text-white/75 hover:bg-white/10 hover:text-white"
                            title="Keluar"
                            aria-label="Keluar dari akun"
                        >
                            <LogOut className="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </aside>

            <header className="sticky top-0 z-30 flex items-center justify-between bg-[#5C3806] px-4 py-3.5 text-white shadow-xs lg:hidden">
                <div className="flex items-center gap-2.5">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-[#F39C12]">
                        <MapPinCheck className="h-4 w-4" />
                    </div>
                    <span className="text-sm font-bold">{title}</span>
                </div>

                <div className="flex items-center gap-2">
                    <Link
                        href="/dashboard"
                        className="flex items-center gap-1 rounded-lg bg-[#0FA958] px-3 py-1.5 text-xs font-bold text-white"
                    >
                        <Hand className="h-3.5 w-3.5" />
                        <span>Check-In</span>
                    </Link>
                    <button
                        type="button"
                        onClick={() => router.post('/logout')}
                        className="rounded-lg bg-white/10 p-1.5 text-white"
                        aria-label="Keluar dari akun"
                    >
                        <LogOut className="h-4 w-4" />
                    </button>
                </div>
            </header>

            <main className="mx-auto w-full max-w-7xl flex-1 p-4 pb-24 sm:p-6 lg:p-8 lg:pb-8">
                {successText && (
                    <div className="mb-5 rounded-2xl border border-[#0FA958]/30 bg-[#E8F8F0] px-4 py-3 text-xs font-bold text-[#0FA958]">
                        {successText}
                    </div>
                )}

                {Object.keys(errors ?? {}).length > 0 && (
                    <div className="mb-5 rounded-2xl border border-[#EF4444]/30 bg-[#FDECEE] px-4 py-3 text-xs font-bold text-[#EF4444]">
                        {Object.values(errors)[0]}
                    </div>
                )}

                {children}
            </main>

            <nav className="fixed inset-x-0 bottom-0 z-30 flex items-center justify-around border-t border-[#EAE4DC] bg-white px-2 py-2 lg:hidden">
                {navItems.map((item) => {
                    const Icon = item.icon;

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`flex flex-col items-center gap-1 rounded-xl px-2 py-1.5 text-[10px] font-bold ${
                                isActive(item.href)
                                    ? 'bg-[#F39C12] text-white'
                                    : 'text-[#7C7269]'
                            }`}
                        >
                            <Icon className="h-4 w-4" />
                            <span>{item.label}</span>
                        </Link>
                    );
                })}
            </nav>
        </div>
    );
}
