'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ChevronRight } from 'lucide-react';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { useState } from 'react';

import { AiPanel } from '@/components/ai';
import { Assignee, useMembers } from '@/components/inbox';
import { QueryState } from '@/components/query-state';
import { Section } from '@/components/section';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { getData, sendData } from '@/lib/api';
import { useHref, useTeam } from '@/lib/session';
import { formatDateTime } from '@/lib/time';
import type { Conversation } from '@/lib/types';
import { cn } from '@/lib/utils';

const STATUSES = ['open', 'pending', 'closed'] as const;

export default function ConversationPage() {
    const { t, locale } = useI18n();
    const href = useHref();
    const { conversation: id } = useParams<{ conversation: string }>();
    const { path, slug, me, timezone } = useTeam();
    const client = useQueryClient();
    const key = [slug, 'conversation', id];
    const [note, setNote] = useState('');
    const members = useMembers();

    const conversation = useQuery({
        queryKey: key,
        queryFn: () => getData<Conversation>(path(`/inbox/${id}`)),
        refetchInterval: 15_000,
    });

    const onSuccess = (data: Conversation) => {
        client.setQueryData(key, data);
        void client.invalidateQueries({ queryKey: [slug, 'inbox'] });
    };

    const update = useMutation({
        mutationFn: (changes: { status?: string; assigned_user_id?: number | null }) =>
            sendData<Conversation>('PATCH', path(`/inbox/${id}`), changes),
        onSuccess,
    });

    const addNote = useMutation({
        mutationFn: (body: string) => sendData<Conversation>('POST', path(`/inbox/${id}/notes`), { body }),
        onSuccess: (data) => {
            onSuccess(data);
            setNote('');
        },
    });

    return (
        <>
            <Link href={href(`/t/${slug}/inbox`)} className="flex items-center gap-1 text-sm text-link hover:underline">
                <ChevronRight className="size-4 ltr:-scale-x-100" />
                {t.inbox.title}
            </Link>
            <QueryState query={conversation}>
                {(data) => (
                    <div className="grid gap-6 lg:grid-cols-[1fr_18rem]">
                        <Section title={t.inbox.messages} testId="conversation-thread">
                            <h1 dir="ltr" className="text-start font-numeric text-lg tabular-nums">
                                {data.phone ?? '—'}
                            </h1>
                            <ol className="flex flex-col gap-3">
                                {data.messages.map((message, index) => {
                                    const inbound = message.direction === 'inbound';

                                    return (
                                        <li
                                            key={message.id ?? index}
                                            className={cn(
                                                'flex max-w-[85%] flex-col gap-1 rounded-xl px-4 py-2.5 text-sm',
                                                inbound ? 'self-start rounded-ss-none bg-muted' : 'self-end rounded-se-none bg-accent',
                                            )}
                                        >
                                            {message.source?.startsWith('ai_') && (
                                                <span className="text-xs text-muted-foreground" data-testid="ai-message">
                                                    {message.source === 'ai_draft' ? t.ai.fromAiApproved : t.ai.fromAi}
                                                </span>
                                            )}
                                            {message.template && (
                                                <span className="text-xs text-muted-foreground">
                                                    {t.inbox.template}: <span dir="ltr">{message.template}</span>
                                                </span>
                                            )}
                                            <span className="whitespace-pre-wrap" dir="auto">
                                                {message.body ?? t.inbox.noBody}
                                            </span>
                                            <time className="text-[11px] text-muted-foreground" dateTime={message.at}>
                                                {formatDateTime(message.at, locale, timezone)}
                                            </time>
                                        </li>
                                    );
                                })}
                            </ol>
                            <p className="text-xs text-muted-foreground">{t.inbox.replyHint}</p>
                        </Section>

                        <div className="flex flex-col gap-6">
                            <AiPanel conversation={data} />

                            <Section title={t.inbox.status}>
                                <div className="flex flex-wrap gap-1">
                                    {STATUSES.map((status) => (
                                        <Button
                                            key={status}
                                            size="sm"
                                            variant={data.status === status ? 'secondary' : 'ghost'}
                                            aria-pressed={data.status === status}
                                            disabled={update.isPending}
                                            onClick={() => update.mutate({ status })}
                                        >
                                            {t.inbox.filter[status]}
                                        </Button>
                                    ))}
                                </div>
                                <div className="flex flex-col gap-2 text-sm">
                                    <span className="text-muted-foreground">
                                        {t.inbox.assignee}: <Assignee userId={data.assigned_user_id} />
                                    </span>
                                    <label className="flex flex-col gap-1.5">
                                        <span className="text-xs text-muted-foreground">{t.inbox.assignTo}</span>
                                        <select
                                            value={data.assigned_user_id ?? ''}
                                            disabled={update.isPending || !members.data}
                                            onChange={(event) =>
                                                update.mutate({ assigned_user_id: event.target.value === '' ? null : Number(event.target.value) })
                                            }
                                            className="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                                        >
                                            <option value="">{t.inbox.unassigned}</option>
                                            {members.data?.members.map((member) => (
                                                <option key={member.id} value={member.id}>
                                                    {member.id === me?.id ? `${member.name} (${t.inbox.you})` : member.name}
                                                </option>
                                            ))}
                                        </select>
                                    </label>
                                    {data.assigned_user_id === me?.id ? (
                                        <Button size="sm" variant="outline" disabled={update.isPending} onClick={() => update.mutate({ assigned_user_id: null })}>
                                            {t.inbox.release}
                                        </Button>
                                    ) : (
                                        <Button size="sm" disabled={update.isPending} onClick={() => update.mutate({ assigned_user_id: me?.id ?? null })}>
                                            {t.inbox.take}
                                        </Button>
                                    )}
                                </div>
                                {update.isError && <p className="text-sm text-destructive">{t.common.error}</p>}
                            </Section>

                            <Section title={t.inbox.notes}>
                                {data.notes.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">{t.inbox.notesEmpty}</p>
                                ) : (
                                    <ul className="flex flex-col gap-2 text-sm">
                                        {data.notes.map((item, index) => (
                                            <li key={index} className="rounded-md bg-muted px-3 py-2">
                                                <p className="whitespace-pre-wrap" dir="auto">{item.body}</p>
                                                <time className="text-[11px] text-muted-foreground" dateTime={item.at}>
                                                    {formatDateTime(item.at, locale, timezone)}
                                                </time>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <form
                                    className="flex flex-col gap-2"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        addNote.mutate(note.trim());
                                    }}
                                >
                                    <textarea
                                        aria-label={t.inbox.notes}
                                        placeholder={t.inbox.notePlaceholder}
                                        maxLength={2000}
                                        rows={3}
                                        value={note}
                                        onChange={(event) => setNote(event.target.value)}
                                        className="rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                    />
                                    {addNote.isError && <p className="text-sm text-destructive">{t.common.error}</p>}
                                    <Button type="submit" size="sm" variant="outline" disabled={addNote.isPending || note.trim() === ''}>
                                        {t.inbox.addNote}
                                    </Button>
                                </form>
                            </Section>
                        </div>
                    </div>
                )}
            </QueryState>
        </>
    );
}
