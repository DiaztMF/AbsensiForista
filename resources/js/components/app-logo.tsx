import { usePage } from '@inertiajs/react';
import type { Auth } from '@/types/auth';

type PageProps = {
    name: string;
};

import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo({ className }: { className?: string }) {
    const { name } = usePage<PageProps>().props;

    return (
        <>
            <div className={`flex items-center justify-center ${className ?? ''}`}>
                <AppLogoIcon className="h-10 w-10" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {name}
                </span>
            </div>
        </>
    );
}
