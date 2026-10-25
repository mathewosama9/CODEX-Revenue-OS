'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Bot, Hand, Undo2 } from 'lucide-react';
import { useState } from 'react';

import { QueryState } from '@/components/query-state';
import { Section } from '@/components/section';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import type { Dictionary } from '@/i18n/ar';
import { ApiError, getData, sendData } from '@/lib/api';
import { useTeam } from '@/lib/session';
import { formatDateTime } from '@/lib/time';
import type { AiActivity, Conversation, ReplyDraft } from '@/lib/types';
import { cn } from '@/lib/utils';

/** Inbox reason, including the AI's handoff reasons (`ai_` + reason). */
export function reasonLabel(t: Dictionary, reason: string | null): string {
    if (!reason) {
        return '';
    }

    if (reason.startsWith('ai_') && t.ai.reason[reason.slice(3)]) {
        return `${t.ai.panel}: ${t.ai.reason[reason.slice(3)]}`;
    }

    return t.inbox.reason[reason] ?? reason;
}

/**
 * One AI draft: approve as is, edit then send, or reject (US-E11-01).
 * Nothing reaches the customer before a member clicks send.
 */
export function DraftCard({ draft, onDone }: { draft: ReplyDraft; onDone: () => void }) {
    const { t, locale } = useI18n();
    const { path, can } = useTeam();
    const [editing, setEditing] = useState(false);
    const [text, setText] = useState(draft.body);
    const [notice, setNotice] = useState<string | null>(null);
    const pending = draft.status === 'pending';

    const decide = useMutation({
        mutationFn: ({ action, body }: { action: 'approve' | 'reject'; body?: string }) =>
            sendData<ReplyDraft>('POST', path(`/ai/drafts/${draft.id}/${action}`), body === undefined ? {} : { body }),
        onMutate: () => setNotice(null),
        onSuccess: (result) => {
            setNotice(result.send_error ? (t.ai.sendError[result.send_error] ?? t.ai.draftStatus[result.status]) : null);
            onDone();
        },
        onError: (error) => {
            setNotice(error instanceof ApiError && error.status === 422 ? t.ai.alreadyDecided : t.common.error);
            onDone();
        },
    });

    return (
        <div data-testid="ai-draft" data-status={draft.status} className="flex flex-col gap-3 rounded-xl border border-dashed bg-muted/40 p-4">
            <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                <span className="flex items-center gap-1.5 font-medium text-foreground">
                    <Bot className="size-4" />
                    {t.ai.draftTitle}
                </span>
                <span>
                    {t.ai.draftStatus[draft.status] ?? draft.status} · <time dateTime={draft.at}>{formatDateTime(draft.at, locale)}</time>
                </span>
            </div>

            {editing ? (
                <textarea
                    aria-label={t.ai.edit}
                    rows={4}
                    maxLength={1500}
                    value={text}
                    dir="auto"
                    onChange={(event) => setText(event.target.value)}
                    className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
            ) : (
                <p className="whitespace-pre-wrap text-sm" dir="auto">
                    {draft.final_body ?? draft.body}
                </p>
            )}

            {draft.send_error && !notice && <p className="text-xs text-destructive">{t.ai.sendError[draft.send_error] ?? draft.send_error}</p>}
            {notice && <p className="text-xs text-destructive" role="alert">{notice}</p>}

            {pending && can('ai-drafts:review') && (
                <>
                    <p className="text-xs text-muted-foreground">{t.ai.draftHelp}</p>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            size="sm"
                            disabled={decide.isPending || text.trim() === ''}
                            onClick={() => decide.mutate({ action: 'approve', body: editing ? text : undefined })}
                        >
                            {editing ? t.ai.approveEdited : t.ai.approve}
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={decide.isPending}
                            onClick={() => {
                                setEditing(!editing);
                                setText(draft.body);
                            }}
                        >
                            {editing ? t.ai.cancelEdit : t.ai.edit}
                        </Button>
                        <Button size="sm" variant="ghost" disabled={decide.isPending} onClick={() => decide.mutate({ action: 'reject' })}>
                            {t.ai.reject}
                        </Button>
                    </div>
                </>
            )}
        </div>
    );
}

/**
 * The AI side of one conversation (E12b): takeover / hand back, the pending
 * draft, and a read-only log of what the agent did and why it handed off.
 */
export function AiPanel({ conversation }: { conversation: Conversation }) {
    const { t, locale } = useI18n();
    const { path, slug } = useTeam();
    const client = useQueryClient();
    const key = [slug, 'conversation-ai', conversation.id];

    const activity = useQuery({
        queryKey: key,
        queryFn: () => getData<AiActivity>(path(`/inbox/${conversation.id}/ai`)),
        refetchInterval: 15_000,
    });

    const refresh = () => {
        void client.invalidateQueries({ queryKey: key });
        void client.invalidateQueries({ queryKey: [slug, 'conversation', String(conversation.id)] });
        void client.invalidateQueries({ queryKey: [slug, 'drafts'] });
    };

    const pause = useMutation({
        mutationFn: (paused: boolean) =>
            sendData<Conversation>('POST', path(`/inbox/${conversation.id}/${paused ? 'takeover' : 'hand-back'}`)),
        onSuccess: (data) => {
            client.setQueryData([slug, 'conversation', String(conversation.id)], data);
            refresh();
        },
    });

    const paused = conversation.ai_paused;

    return (
        <Section
            title={t.ai.panel}
            testId="ai-panel"
            actions={
                <span
                    data-testid="ai-state"
                    data-paused={paused}
                    className={cn(
                        'rounded-full border px-2.5 py-0.5 text-xs font-medium',
                        paused ? 'border-muted-foreground text-muted-foreground' : 'border-gain text-gain',
                    )}
                >
                    {paused ? '○' : '●'}
                </span>
            }
        >
            <p className="text-sm">{paused ? t.ai.paused : t.ai.active}</p>
            {paused ? (
                <Button size="sm" variant="outline" disabled={pause.isPending} onClick={() => pause.mutate(false)}>
                    <Undo2 className="ltr:-scale-x-100" />
                    {t.ai.handBack}
                </Button>
            ) : (
                <>
                    <Button size="sm" disabled={pause.isPending} onClick={() => pause.mutate(true)}>
                        <Hand />
                        {t.ai.takeover}
                    </Button>
                    <p className="text-xs text-muted-foreground">{t.ai.takeoverHelp}</p>
                </>
            )}
            {pause.isError && <p className="text-sm text-destructive">{t.common.error}</p>}

            <QueryState query={activity}>
                {(data) => {
                    const pendingDraft = data.runs.find((run) => run.draft?.status === 'pending')?.draft ?? null;

                    return (
                        <>
                            {pendingDraft && <DraftCard key={pendingDraft.id} draft={pendingDraft} onDone={refresh} />}

                            <div className="flex flex-col gap-2">
                                <h3 className="text-sm font-medium">{t.ai.log}</h3>
                                {data.runs.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">{t.ai.logEmpty}</p>
                                ) : (
                                    <ol className="flex flex-col gap-2 text-xs" data-testid="ai-log">
                                        {data.runs.map((run) => {
                                            const tools = [...new Set(run.tools.map((step) => step.tool).filter((tool): tool is string => !!tool))];
                                            const blocked = run.tools.flatMap((step) => step.blocked_numbers ?? []);

                                            return (
                                                <li key={run.id} className="flex flex-col gap-0.5 rounded-md bg-muted px-3 py-2">
                                                    <span className="font-medium">
                                                        {t.ai.runStatus[run.status] ?? run.status}
                                                        {run.handoff_reason && ` · ${t.ai.reason[run.handoff_reason] ?? run.handoff_reason}`}
                                                    </span>
                                                    {tools.length > 0 && (
                                                        <span className="text-muted-foreground">{tools.map((tool) => t.ai.tools[tool] ?? tool).join(locale === 'ar' ? '، ' : ', ')}</span>
                                                    )}
                                                    {blocked.length > 0 && (
                                                        <span className="text-muted-foreground">
                                                            {t.ai.blocked}:{' '}
                                                            <span dir="ltr" className="font-numeric">
                                                                {blocked.join(', ')}
                                                            </span>
                                                        </span>
                                                    )}
                                                    <time className="text-[11px] text-muted-foreground" dateTime={run.at}>
                                                        {formatDateTime(run.at, locale)}
                                                    </time>
                                                </li>
                                            );
                                        })}
                                    </ol>
                                )}
                            </div>
                        </>
                    );
                }}
            </QueryState>
        </Section>
    );
}
