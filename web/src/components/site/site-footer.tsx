import Link from 'next/link';

import { Lockup } from '@/components/lockup';
import { siteHref } from '@/i18n/site';
import type { SiteDictionary } from '@/i18n/site';
import { getSite } from '@/i18n/site/server';

type NavKey = keyof SiteDictionary['common']['nav'];

// Every site page, in three columns.
const COLUMNS: { title: 'product' | 'companyLinks' | 'legal'; links: { key: NavKey; path: string }[] }[] = [
    {
        title: 'product',
        links: [
            { key: 'howItWorks', path: '/how-it-works' },
            { key: 'features', path: '/features' },
            { key: 'aiAgent', path: '/ai-agent' },
            { key: 'pilot', path: '/pilot' },
            { key: 'roadmap', path: '/roadmap' },
        ],
    },
    {
        title: 'companyLinks',
        links: [
            { key: 'security', path: '/security' },
            { key: 'faq', path: '/faq' },
            { key: 'contact', path: '/contact' },
        ],
    },
    {
        title: 'legal',
        links: [
            { key: 'privacy', path: '/privacy' },
            { key: 'terms', path: '/terms' },
        ],
    },
];

export async function SiteFooter() {
    const { locale, s } = await getSite();

    return (
        <footer className="border-t bg-sunken px-4 py-12">
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-10">
                <div className="grid gap-10 md:grid-cols-4">
                    <div className="flex flex-col gap-2">
                        <Link href={siteHref(locale, '')} aria-label={s.common.nav.home} className="w-fit">
                            <Lockup />
                        </Link>
                        <p className="text-sm text-muted-foreground">{s.common.footer.tagline}</p>
                    </div>
                    {COLUMNS.map((column) => (
                        <nav key={column.title} aria-label={s.common.footer[column.title]} className="flex flex-col gap-3">
                            <h2 className="text-sm font-semibold">{s.common.footer[column.title]}</h2>
                            <ul className="flex flex-col gap-2">
                                {column.links.map((link) => (
                                    <li key={link.path}>
                                        <Link href={siteHref(locale, link.path)} className="text-sm text-muted-foreground hover:text-foreground">
                                            {s.common.nav[link.key]}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    ))}
                </div>
                <div className="flex flex-col gap-1 border-t pt-6 text-xs text-muted-foreground">
                    <span dir="ltr" className="w-fit">{s.common.footer.company}</span>
                    <span>{s.common.footer.trademark}</span>
                </div>
            </div>
        </footer>
    );
}
