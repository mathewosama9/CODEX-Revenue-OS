'use client';

import { useQuery, useQueryClient } from '@tanstack/react-query';
import { ChevronLeft } from 'lucide-react';
import Link from 'next/link';
import { useState } from 'react';

import { DraftCard } from '@/components/ai';
import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { getData } from '@/lib/api';
import { useHref, useTeam } from '@/lib/session';
import type { ReplyDraft } from '@/lib/types';

const FILTERS = ['pending', 'decided'] as const;

export default function DraftsPage() {
    const { t } = useI18n();
    const href = useHref();
    const { path, slug } = useTeam();
    const client = useQueryClient();
    const [status, setStatus] = useState<(typeof FILTERS)[number]>('pending');
    const key = [slug, 'ai-drafts', status];

    const drafts = useQuery({
        queryKey: key,
        queryFn: () => getData<ReplyDraft[]>(path(`/ai/drafts?status=${status}`)),
        // Customers wait on these; keep the queue fresh.
        refetchInterval: 15_000,
    });

    // A decision moves the draft between tabs, so refresh both.
    const refresh = () => void client.invalidateQueries({ queryKey: [slug, 'ai-drafts'] });

    return (
        <>
            <PageHeader title={t.ai.drafts.title} subtitle={t.ai.drafts.subtitle} />
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
                        {t.ai.drafts[filter]}
                    </Button>
                ))}
            </div>
            <QueryState query={drafts}>
                {(items) =>
                    items.length === 0 ? (
                        <p className="text-sm text-muted-foreground">{t.ai.drafts.empty}</p>
                    ) : (
                        <ul className="flex flex-col gap-3" data-testid="drafts-list">
                            {items.map((draft) => (
                                <li key={draft.id} className="flex flex-col gap-3 rounded-2xl rounded-ss-none border bg-card p-5 shadow-card">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <span dir="ltr" className="text-start font-numeric tabular-nums">
                                            {draft.phone ?? '—'}
                                        </span>
                                        <Link
                                            href={href(`/t/${slug}/inbox/${draft.conversation_id}`)}
                                            className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            {t.ai.drafts.open}
                                            <ChevronLeft className="size-4 ltr:-scale-x-100" />
                                        </Link>
                                    </div>
                                    <DraftCard key={`${draft.id}-${draft.status}`} draft={draft} onDone={refresh} />
                                </li>
                            ))}
                        </ul>
                    )
                }
            </QueryState>
        </>
    );
}
