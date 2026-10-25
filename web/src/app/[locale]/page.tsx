'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useEffect } from 'react';

import { ApiDown } from '@/components/app-shell';
import { Lockup } from '@/components/lockup';
import { Preferences } from '@/components/preferences';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { format, useI18n } from '@/i18n';
import { sendData } from '@/lib/api';
import { homeFor } from '@/lib/nav';
import { isUnauthenticated, useHref, useMe } from '@/lib/session';
import type { Me } from '@/lib/session';

/** Workspace picker: opens the only workspace directly, unless an invitation waits. */
export default function WorkspacesPage() {
    const { t } = useI18n();
    const href = useHref();
    const router = useRouter();
    const { data: me, error, refetch } = useMe();
    const client = useQueryClient();
    const respond = useMutation({
        mutationFn: ({ code, accept }: { code: string; accept: boolean }) =>
            sendData<Me>(accept ? 'POST' : 'DELETE', `/invitations/${code}${accept ? '/accept' : ''}`),
        onSuccess: (fresh) => client.setQueryData(['me'], fresh),
    });
    const direct = me?.teams.length === 1 && me.invitations.length === 0;

    useEffect(() => {
        if (isUnauthenticated(error)) {
            router.replace(href('/login'));
        } else if (me && direct) {
            router.replace(href(`/t/${me.teams[0].slug}${homeFor(me.teams[0])}`));
        }
    }, [me, direct, error, router, href]);

    if (error && !isUnauthenticated(error)) {
        return <ApiDown onRetry={() => void refetch()} />;
    }

    return (
        <div className="flex min-h-screen flex-col">
            <header className="flex items-center justify-between px-6 py-4">
                <Lockup />
                <Preferences />
            </header>
            <main className="mx-auto flex w-full max-w-md flex-col gap-4 px-4 pt-[8vh]">
                {!me || direct ? (
                    <Spinner className="mx-auto" />
                ) : (
                    <>
                        {me.invitations.length > 0 && (
                            <section className="flex flex-col gap-2" data-testid="invitations">
                                <h2 className="text-base font-semibold">{t.teams.invitationsTitle}</h2>
                                {me.invitations.map((invitation) => (
                                    <div
                                        key={invitation.code}
                                        className="flex flex-wrap items-center justify-between gap-3 rounded-xl rounded-ss-none border border-primary bg-card px-5 py-4 shadow-card"
                                    >
                                        <span className="flex flex-col">
                                            <span className="font-medium">{invitation.team_name}</span>
                                            <span className="text-xs text-muted-foreground">
                                                {format(t.teams.invitedAs, { role: t.teams.role[invitation.role] })}
                                            </span>
                                        </span>
                                        <span className="flex gap-2">
                                            <Button size="sm" variant="outline" disabled={respond.isPending} onClick={() => respond.mutate({ code: invitation.code, accept: false })}>
                                                {t.teams.decline}
                                            </Button>
                                            <Button size="sm" disabled={respond.isPending} onClick={() => respond.mutate({ code: invitation.code, accept: true })}>
                                                {t.teams.accept}
                                            </Button>
                                        </span>
                                    </div>
                                ))}
                                {respond.isError && <p className="text-sm text-destructive">{t.common.error}</p>}
                            </section>
                        )}
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
