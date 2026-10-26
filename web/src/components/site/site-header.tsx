'use client';

import { Menu } from 'lucide-react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useState } from 'react';

import { Lockup } from '@/components/lockup';
import { Preferences } from '@/components/preferences';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet';
import { siteHref } from '@/i18n/site';
import { useSite } from '@/i18n/site/client';
import { cn } from '@/lib/utils';

const LINKS = [
    { key: 'howItWorks', path: '/how-it-works' },
    { key: 'features', path: '/features' },
    { key: 'aiAgent', path: '/ai-agent' },
    { key: 'pilot', path: '/pilot' },
    { key: 'security', path: '/security' },
] as const;

/** Sticky site header: the pilot banner, page links, preferences, sign in and the pilot CTA. */
export function SiteHeader() {
    const { locale, s } = useSite();
    const pathname = usePathname();
    const [open, setOpen] = useState(false);

    const links = (vertical: boolean) =>
        LINKS.map((link) => {
            const target = siteHref(locale, link.path);
            const active = pathname === target || pathname.startsWith(`${target}/`);

            return (
                <Link
                    key={link.path}
                    href={target}
                    onClick={() => setOpen(false)}
                    aria-current={active ? 'page' : undefined}
                    className={cn(
                        'rounded-md px-3 py-2 text-sm',
                        active ? 'font-medium text-foreground' : 'text-muted-foreground hover:text-foreground',
                        active && (vertical ? 'bg-accent' : 'underline decoration-link decoration-2 underline-offset-8'),
                    )}
                >
                    {s.common.nav[link.key]}
                </Link>
            );
        });

    const actions = (
        <>
            <Button asChild variant="outline">
                <Link href={siteHref(locale, '/login')} onClick={() => setOpen(false)}>
                    {s.common.cta.signIn}
                </Link>
            </Button>
            <Button asChild>
                <Link href={siteHref(locale, '/pilot')} onClick={() => setOpen(false)}>
                    {s.common.cta.requestPilot}
                </Link>
            </Button>
        </>
    );

    return (
        <header className="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
            <p className="bg-sunken px-4 py-1.5 text-center text-xs text-muted-foreground">{s.common.pilotBanner}</p>
            <div className="mx-auto flex w-full max-w-6xl items-center gap-4 px-4 py-3">
                <Link href={siteHref(locale, '')} aria-label={s.common.nav.home} onClick={() => setOpen(false)}>
                    <Lockup />
                </Link>
                <nav aria-label={s.common.nav.menu} className="hidden items-center gap-1 lg:flex">
                    {links(false)}
                </nav>
                <div className="ms-auto hidden items-center gap-2 lg:flex">
                    <Preferences />
                    {actions}
                </div>
                <Button
                    variant="ghost"
                    size="icon"
                    className="ms-auto lg:hidden"
                    onClick={() => setOpen(!open)}
                    aria-label={s.common.nav.menu}
                    aria-expanded={open}
                >
                    <Menu />
                </Button>
            </div>

            {/* Mobile: a real dialog, like the app shell (focus trapped, Escape closes). */}
            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="left" className="w-72 gap-6 p-4 pt-12">
                    <SheetTitle className="sr-only">{s.common.nav.menu}</SheetTitle>
                    <nav aria-label={s.common.nav.menu} className="flex flex-col gap-1">
                        {links(true)}
                    </nav>
                    <div className="flex flex-col gap-2">{actions}</div>
                    <Preferences />
                </SheetContent>
            </Sheet>
        </header>
    );
}
