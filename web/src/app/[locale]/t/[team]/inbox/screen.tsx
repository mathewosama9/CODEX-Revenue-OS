'use client';

import { useInfiniteQuery } from '@tanstack/react-query';
import { ChevronLeft } from 'lucide-react';
import Link from 'next/link';
import { useState } from 'react';

import { Assignee } from '@/components/inbox';
import { KillSwitch } from '@/components/kill-switch';
import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { api } from '@/lib/api';
import { useHref, useTeam } from '@/lib/session';
import { formatRelative } from '@/lib/time';
import type { ConversationSummary } from '@/lib/types';

const FILTERS = ['open', 'pending', 'closed', 'all'] as const;

export default function InboxPage() {
    const { t, locale } = useI18n();
    const href = useHref();
    const { path, slug } = useTeam();
    const [status, setStatus] = useState<(typeof FILTERS)[number]>('open');

    // 50 at a time, newest first; "load more" follows the API cursor (review L1).
    const conversations = useInfiniteQuery({
        queryKey: [slug, 'inbox', status],
        queryFn: ({ pageParam }) =>
            api<{ data: ConversationSummary[]; meta: { next_cursor: string | null } }>(
                'GET',
                `/api/v1${path(`/inbox?status=${status}${pageParam ? `&cursor=${pageParam}` : ''}`)}`,
            ),
        initialPageParam: '',
        getNextPageParam: (page) => page.meta.next_cursor ?? undefined,
        select: (data) => data.pages.flatMap((page) => page.data),
        refetchInterval: 20_000,
    });

    return (
        <>
            <PageHeader title={t.inbox.title} actions={<KillSwitch />} />
            <div className="flex flex-wrap gap-1" role="tablist">
                {FILTERS.map((filter) => (
                    <Button
                        key={filter}
                        role="tab"
                        aria-selected={status === filter}
                        size="sm"
                        variant={status === filter ? 'secondary' : 'ghost'}
                        onClick={() => setStatus(filter)}
                    >
                        {t.inbox.filter[filter]}
                    </Button>
                ))}
            </div>
            <QueryState query={conversations}>
                {(items) =>
                    items.length === 0 ? (
                        <p className="text-sm text-muted-foreground">{t.inbox.empty}</p>
                    ) : (
                        <ul className="divide-y rounded-2xl rounded-ss-none border bg-card shadow-card" data-testid="inbox-list">
                            {items.map((conversation) => (
                                <li key={conversation.id}>
                                    <Link
                                        href={href(`/t/${slug}/inbox/${conversation.id}`)}
                                        className="flex items-center justify-between gap-4 px-5 py-4 hover:bg-accent/40"
                                    >
                                        <span className="flex flex-col gap-1">
                                            <span dir="ltr" className="text-start font-numeric tabular-nums">
                                                {conversation.phone ?? '—'}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {conversation.reason ? (t.inbox.reason[conversation.reason] ?? conversation.reason) : ''}
                                                {' · '}
                                                <Assignee userId={conversation.assigned_user_id} />
                                            </span>
                                        </span>
                                        <span className="flex items-center gap-2 text-xs text-muted-foreground">
                                            {formatRelative(conversation.last_message_at, locale)}
                                            <ChevronLeft className="size-4 ltr:-scale-x-100" />
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )
                }
            </QueryState>
            {conversations.hasNextPage && (
                <Button variant="outline" className="self-center" disabled={conversations.isFetchingNextPage} onClick={() => void conversations.fetchNextPage()}>
                    {t.inbox.loadMore}
                </Button>
            )}
        </>
    );
}
