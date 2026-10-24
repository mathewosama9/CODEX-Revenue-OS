import type { Metadata } from 'next';
import { IBM_Plex_Sans_Arabic, Rubik } from 'next/font/google';
import { locale as localeParam } from 'next/root-params';
import { notFound } from 'next/navigation';

import { Providers } from '@/components/providers';
import { dir, isLocale, LOCALES } from '@/i18n/config';

import '../globals.css';

const plex = IBM_Plex_Sans_Arabic({
    subsets: ['arabic', 'latin'],
    weight: ['400', '500', '600', '700'],
    variable: '--font-plex',
});

const rubik = Rubik({
    subsets: ['latin'],
    weight: ['500', '600'],
    variable: '--font-rubik',
});

export const metadata: Metadata = {
    title: 'CODEX Commerce',
    robots: { index: false, follow: false },
};

// Navy is the default theme; the script switches to Light before first paint
// when the merchant picked it, so there is no flash.
const THEME_SCRIPT = `try{if(localStorage.getItem('theme')==='light')document.documentElement.classList.remove('dark')}catch(e){}`;

export function generateStaticParams() {
    return LOCALES.map((locale) => ({ locale }));
}

export default async function RootLayout({ children }: LayoutProps<'/[locale]'>) {
    const locale = await localeParam();

    if (!isLocale(locale)) {
        notFound();
    }

    return (
        <html
            lang={locale}
            dir={dir(locale)}
            className={`dark ${plex.variable} ${rubik.variable}`}
            suppressHydrationWarning
        >
            <head>
                <script dangerouslySetInnerHTML={{ __html: THEME_SCRIPT }} />
            </head>
            <body className="min-h-screen font-sans antialiased">
                <Providers locale={locale}>{children}</Providers>
            </body>
        </html>
    );
}
