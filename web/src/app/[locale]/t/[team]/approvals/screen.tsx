'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { Button } from '@/components/ui/button';
import { format, useI18n } from '@/i18n';
import { formatSar } from '@/lib/money';
import { ApiError, getData, sendData } from '@/lib/api';
import { useTeam } from '@/lib/session';
import { formatDateTime, formatRelative } from '@/lib/time';
import type { Approval } from '@/lib/types';
import { cn } from '@/lib/utils';

export default function ApprovalsPage() {
    const { t, locale } = useI18n();
    const { path, slug } = useTeam();
    const client = useQueryClient();
    const [notice, setNotice] = useState<string | null>(null);
    // Re-render every 30s so "expires in N minutes" counts down (review L8).
    const [, setTick] = useState(0);

    useEffect(() => {
        const timer = window.setInterval(() => setTick((tick) => tick + 1), 30_000);

        return () => window.clearInterval(timer);
    }, []);
    const key = [slug, 'approvals'];

    const approvals = useQuery({
        queryKey: key,
        queryFn: () => getData<Approval[]>(path('/recovery/approvals')),
        // Approvals expire within minutes; keep the list fresh.
        refetchInterval: 30_000,
    });

    const decide = useMutation({
        mutationFn: ({ id, decision }: { id: number; decision: 'approve' | 'reject' }) =>
            sendData<Approval[]>('POST', path(`/recovery/approvals/${id}`), { decision }),
        onMutate: () => setNotice(null),
        onSuccess: (data) => client.setQueryData(key, data),
        onError: (error) => {
            setNotice(
                error instanceof ApiError && error.status === 410
                    ? t.approvals.expired
                    : error instanceof ApiError && error.status === 409
                      ? t.approvals.moot
                      : t.common.error,
            );
            void client.invalidateQueries({ queryKey: key });
        },
    });

    return (
        <>
            <PageHeader title={t.approvals.title} subtitle={t.approvals.subtitle} />
            {notice && <p className="text-sm text-destructive">{notice}</p>}
            <QueryState query={approvals}>
                {(items) =>
                    items.length === 0 ? (
                        <p className="text-sm text-muted-foreground">{t.approvals.empty}</p>
                    ) : (
                        <ul className="flex flex-col gap-3" data-testid="approvals">
                            {items.map((approval, index) => (
                                <li
                                    key={approval.id}
                                    className={cn(
                                        'flex flex-wrap items-center justify-between gap-4 rounded-2xl rounded-ss-none border bg-card p-5 shadow-card',
                                        index === 0 && 'border-border-strong',
                                    )}
                                >
                                    <div className="flex flex-col gap-1">
                                        <span className="flex items-baseline gap-2">
                                            <span dir="ltr" className="font-numeric text-2xl font-semibold tabular-nums">
                                                {approval.percent}%
                                            </span>
                                            <span className="text-sm text-muted-foreground">{t.approvals.percent}</span>
                                        </span>
                                        <span className="text-sm">
                                            {t.approvals.reason[approval.reason] ?? approval.reason}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {/* The cart's value is what the merchant recognises; our id means nothing in Salla. */}
                                            {approval.cart_total_halalas === null ? (
                                                <>
                                                    {t.approvals.cart} <span className="font-numeric">#{approval.cart_id}</span>
                                                </>
                                            ) : (
                                                format(t.approvals.cartWorth, { amount: formatSar(approval.cart_total_halalas, locale) })
                                            )}
                                            {' · '}
                                            {t.approvals.expires}{' '}
                                            <time dateTime={approval.expires_at} title={formatDateTime(approval.expires_at, locale)}>
                                                {formatRelative(approval.expires_at, locale)}
                                            </time>
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            disabled={decide.isPending}
                                            onClick={() => decide.mutate({ id: approval.id, decision: 'reject' })}
                                        >
                                            {t.approvals.reject}
                                        </Button>
                                        {/* Gold is for money, once per screen: only the most urgent request. */}
                                        <Button
                                            variant={index === 0 ? 'revenue' : 'secondary'}
                                            disabled={decide.isPending}
                                            onClick={() => decide.mutate({ id: approval.id, decision: 'approve' })}
                                        >
                                            {t.approvals.approve}
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )
                }
            </QueryState>
        </>
    );
}
