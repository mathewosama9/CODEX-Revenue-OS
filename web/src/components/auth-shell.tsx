'use client';

import { useRouter } from 'next/navigation';
import { useEffect } from 'react';
import type { ReactNode } from 'react';

import { Lockup } from '@/components/lockup';
import { Preferences } from '@/components/preferences';
import { useHref, useMe } from '@/lib/session';

/** Signed-out pages; a signed-in visitor goes straight to their workspaces. */
export function AuthShell({ children }: { children: ReactNode }) {
    const { data: me } = useMe();
    const router = useRouter();
    const href = useHref();

    useEffect(() => {
        if (me) {
            router.replace(href('/workspaces'));
        }
    }, [me, router, href]);

    return (
        <div className="flex min-h-screen flex-col">
            <header className="flex items-center justify-between px-6 py-4">
                <Lockup />
                <Preferences />
            </header>
            <main className="flex flex-1 items-start justify-center px-4 pt-[8vh] pb-12">
                <div className="w-full max-w-sm rounded-2xl rounded-ss-none border bg-card p-8 shadow-card">
                    {children}
                </div>
            </main>
        </div>
    );
}

export function AuthHeading({ title, subtitle }: { title: string; subtitle?: string }) {
    return (
        <div className="mb-6 flex flex-col gap-1">
            <h1 className="text-xl font-semibold">{title}</h1>
            {subtitle && <p className="text-sm text-muted-foreground">{subtitle}</p>}
        </div>
    );
}
