'use client';

import { ChevronsUpDown, LogOut, Menu } from 'lucide-react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';

import { Lockup } from '@/components/lockup';
import { Preferences } from '@/components/preferences';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/i18n';
import { NAV } from '@/lib/nav';
import { isUnauthenticated, useHref, useMe, useSignOut, useTeam } from '@/lib/session';
import { cn } from '@/lib/utils';

export function AppShell({ children }: { children: ReactNode }) {
    const { t } = useI18n();
    const href = useHref();
    const router = useRouter();
    const pathname = usePathname();
    const signOut = useSignOut();
    const { error, refetch } = useMe();
    const [signOutFailed, setSignOutFailed] = useState(false);
    const { me, team, slug, can } = useTeam();
    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (isUnauthenticated(error)) {
            router.replace(href('/login'));
        } else if (me && !team) {
            // Not a member of this workspace: back to the picker.
            router.replace(href(''));
        }
    }, [error, me, team, router, href]);

    // API down, CORS misconfigured, server error: say so, never spin forever (review H8).
    if (error && !isUnauthenticated(error)) {
        return <ApiDown onRetry={() => void refetch()} />;
    }

    if (!team) {
        return (
            <div className="flex min-h-screen items-center justify-center">
                <Spinner />
            </div>
        );
    }

    const nav = (
        <nav className="flex flex-col gap-1">
            {NAV.filter((item) => item.permission === null || can(item.permission)).map((item) => {
                const target = href(`/t/${slug}${item.path}`);
                const active = pathname.startsWith(target);

                return (
                    <Link
                        key={item.path}
                        href={target}
                        onClick={() => setOpen(false)}
                        aria-current={active ? 'page' : undefined}
                        className={cn(
                            'flex items-center gap-3 rounded-md px-3 py-2 text-sm',
                            active
                                ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground'
                                : 'text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                        )}
                    >
                        <item.icon className="size-4" strokeWidth={1.5} />
                        {t.nav[item.label]}
                    </Link>
                );
            })}
        </nav>
    );

    const sidebar = (
        <div className="flex h-full flex-col gap-6 p-4">
            <Lockup className="px-3" />
            <Link
                href={href('')}
                className="flex items-center justify-between rounded-md border px-3 py-2 text-sm hover:border-primary"
                title={t.common.switchWorkspace}
            >
                <span className="flex flex-col">
                    <span className="text-xs text-muted-foreground">{t.common.workspace}</span>
                    <span className="font-medium">{team.name}</span>
                </span>
                {(me?.teams.length ?? 0) > 1 && <ChevronsUpDown className="size-4" />}
            </Link>
            {nav}
            <div className="mt-auto flex flex-col gap-2">
                <span className="truncate px-3 text-xs text-muted-foreground" dir="ltr">
                    {me?.email}
                </span>
                <Button
                    variant="ghost"
                    className="justify-start"
                    onClick={async () => setSignOutFailed(!(await signOut()))}
                >
                    <LogOut className="rtl:-scale-x-100" />
                    {t.common.signOut}
                </Button>
                {signOutFailed && <p className="px-3 text-xs text-destructive">{t.common.signOutFailed}</p>}
            </div>
        </div>
    );

    return (
        <div className="flex min-h-screen">
            <aside className="hidden w-64 shrink-0 border-e bg-sidebar md:block">
                <div className="sticky top-0 h-screen">{sidebar}</div>
            </aside>

            {/* A real dialog on mobile: focus stays inside, Escape closes it (review L6). */}
            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="left" className="w-72 bg-sidebar p-0">
                    <SheetTitle className="sr-only">{t.common.menu}</SheetTitle>
                    {sidebar}
                </SheetContent>
            </Sheet>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="flex items-center justify-between gap-2 border-b px-4 py-3 md:px-8">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="md:hidden"
                        onClick={() => setOpen(!open)}
                        aria-label={t.common.menu}
                    >
                        <Menu />
                    </Button>
                    <span className="text-sm font-medium md:hidden">{team.name}</span>
                    <div className="ms-auto">
                        <Preferences />
                    </div>
                </header>
                <main className="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-8 md:px-8">
                    {children}
                </main>
            </div>
        </div>
    );
}

export function ApiDown({ onRetry }: { onRetry: () => void }) {
    const { t } = useI18n();

    return (
        <div className="flex min-h-screen flex-col items-center justify-center gap-4 px-4 text-center" role="alert">
            <p className="max-w-sm text-sm text-muted-foreground">{t.common.apiDown}</p>
            <Button variant="outline" onClick={onRetry}>
                {t.common.retry}
            </Button>
        </div>
    );
}
