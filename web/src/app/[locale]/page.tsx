'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useEffect } from 'react';

import { Lockup } from '@/components/lockup';
import { Preferences } from '@/components/preferences';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/i18n';
import { homeFor } from '@/lib/nav';
import { isUnauthenticated, useHref, useMe } from '@/lib/session';

/** Workspace picker: opens the only workspace directly. */
export default function WorkspacesPage() {
    const { t } = useI18n();
    const href = useHref();
    const router = useRouter();
    const { data: me, error } = useMe();

    useEffect(() => {
        if (isUnauthenticated(error)) {
            router.replace(href('/login'));
        } else if (me?.teams.length === 1) {
            router.replace(href(`/t/${me.teams[0].slug}${homeFor(me.teams[0])}`));
        }
    }, [me, error, router, href]);

    return (
        <div className="flex min-h-screen flex-col">
            <header className="flex items-center justify-between px-6 py-4">
                <Lockup />
                <Preferences />
            </header>
            <main className="mx-auto flex w-full max-w-md flex-col gap-4 px-4 pt-[8vh]">
                {!me || me.teams.length === 1 ? (
                    <Spinner className="mx-auto" />
                ) : (
                    <>
                        <h1 className="text-xl font-semibold">{t.teams.title}</h1>
                        {me.teams.length === 0 && (
                            <p className="text-sm text-muted-foreground">{t.teams.empty}</p>
                        )}
                        <ul className="flex flex-col gap-2">
                            {me.teams.map((team) => (
                                <li key={team.slug}>
                                    <Link
                                        href={href(`/t/${team.slug}${homeFor(team)}`)}
                                        className="flex items-center justify-between rounded-xl rounded-ss-none border bg-card px-5 py-4 shadow-card hover:border-primary"
                                    >
                                        <span className="font-medium">{team.name}</span>
                                        <span className="text-xs text-muted-foreground">
                                            {t.teams.role[team.role]}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </main>
        </div>
    );
}
