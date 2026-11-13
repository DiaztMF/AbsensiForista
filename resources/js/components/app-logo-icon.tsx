import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/images/logo_forista.png"
            alt="FORISTA"
            {...props}
            loading="eager"
            decoding="async"
        />
    );
}
