'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { ConfirmButton } from '@/components/confirm-button';
import { FormErrors } from '@/components/form-errors';
import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { Section } from '@/components/section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n';
import { getData, sendData } from '@/lib/api';
import { useTeam } from '@/lib/session';
import type { KnowledgeDocument, KnowledgeKind } from '@/lib/types';
import { useSubmit } from '@/lib/use-form';

const KINDS: KnowledgeKind[] = ['faq', 'shipping', 'returns'];

/** Mirrors the backend limit on a document body. */
const BODY_MAX = 20000;

export default function KnowledgePage() {
    const { t } = useI18n();
    const { path, slug } = useTeam();
    const documents = useQuery({
        queryKey: [slug, 'knowledge'],
        queryFn: () => getData<KnowledgeDocument[]>(path('/knowledge')),
    });

    return (
        <>
            <PageHeader title={t.ai.knowledge.title} subtitle={t.ai.knowledge.subtitle} />
            <QueryState query={documents}>
                {(data) => (
                    <>
                        {KINDS.map((kind) => (
                            <KindSection key={kind} kind={kind} documents={data.filter((doc) => doc.kind === kind)} />
                        ))}
                    </>
                )}
            </QueryState>
        </>
    );
}

function KindSection({ kind, documents }: { kind: KnowledgeKind; documents: KnowledgeDocument[] }) {
    const { t } = useI18n();
    const [adding, setAdding] = useState(false);
    const [editing, setEditing] = useState<number | null>(null);

    return (
        <Section
            title={t.ai.knowledge.kinds[kind]}
            testId={`knowledge-${kind}`}
            actions={
                !adding && (
                    <Button variant="outline" size="sm" onClick={() => setAdding(true)}>
                        <Plus />
                        {t.ai.knowledge.add}
                    </Button>
                )
            }
        >
            {documents.length === 0 && !adding && (
                <p className="text-sm text-muted-foreground">{t.ai.knowledge.empty}</p>
            )}
            {documents.length > 0 && (
                <ul className="flex flex-col divide-y">
                    {documents.map((doc) => (
                        <li key={doc.id} className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0" data-testid="knowledge-doc">
                            {editing === doc.id ? (
                                <DocumentForm kind={kind} doc={doc} onDone={() => setEditing(null)} />
                            ) : (
                                <DocumentView doc={doc} onEdit={() => setEditing(doc.id)} />
                            )}
                        </li>
                    ))}
                </ul>
            )}
            {adding && <DocumentForm kind={kind} onDone={() => setAdding(false)} />}
        </Section>
    );
}

function DocumentView({ doc, onEdit }: { doc: KnowledgeDocument; onEdit: () => void }) {
    const { t } = useI18n();
    const { path, slug } = useTeam();
    const client = useQueryClient();
    const remove = useMutation({
        // 204 has no `{ data }` envelope, so this goes through api() directly.
        mutationFn: () => sendData<void>('DELETE', path(`/knowledge/${doc.id}`)),
        onSuccess: () =>
            client.setQueryData<KnowledgeDocument[]>([slug, 'knowledge'], (old) =>
                old?.filter((item) => item.id !== doc.id),
            ),
    });

    return (
        <>
            <div className="flex flex-wrap items-start justify-between gap-3">
                <h3 className="font-medium" dir="auto">
                    {doc.title}
                </h3>
                <div className="flex items-center gap-2">
                    <Button variant="ghost" size="sm" onClick={onEdit}>
                        <Pencil />
                        {t.ai.knowledge.edit}
                    </Button>
                    <ConfirmButton
                        variant="destructive"
                        size="sm"
                        message={t.ai.knowledge.deleteConfirm}
                        onConfirm={() => remove.mutate()}
                        disabled={remove.isPending}
                    >
                        <Trash2 />
                        {t.ai.knowledge.delete}
                    </ConfirmButton>
                </div>
            </div>
            <p className="whitespace-pre-wrap text-sm text-muted-foreground" dir="auto">
                {doc.body}
            </p>
            {remove.isError && <p className="text-sm text-destructive">{t.common.error}</p>}
        </>
    );
}

function DocumentForm({ kind, doc, onDone }: { kind: KnowledgeKind; doc?: KnowledgeDocument; onDone: () => void }) {
    const { t } = useI18n();
    const { path, slug } = useTeam();
    const client = useQueryClient();
    const { busy, errors, failed, run } = useSubmit();
    const [title, setTitle] = useState(doc?.title ?? '');
    const [body, setBody] = useState(doc?.body ?? '');
    const id = doc ? `doc-${doc.id}` : `new-${kind}`;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        void run(async () => {
            const payload = { kind, title, body };
            const saved = doc
                ? await sendData<KnowledgeDocument>('PUT', path(`/knowledge/${doc.id}`), payload)
                : await sendData<KnowledgeDocument>('POST', path('/knowledge'), payload);

            client.setQueryData<KnowledgeDocument[]>([slug, 'knowledge'], (old = []) =>
                doc ? old.map((item) => (item.id === saved.id ? saved : item)) : [...old, saved],
            );
            onDone();
        });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-4 rounded-md border p-4" data-testid="knowledge-form">
            <div className="flex flex-col gap-1.5">
                <Label htmlFor={`${id}-title`}>{t.ai.knowledge.docTitle}</Label>
                <Input
                    id={`${id}-title`}
                    dir="auto"
                    required
                    maxLength={255}
                    value={title}
                    onChange={(event) => setTitle(event.target.value)}
                    aria-invalid={errors.title ? true : undefined}
                />
                {errors.title && <p className="text-xs text-destructive" role="alert">{errors.title}</p>}
            </div>
            <div className="flex flex-col gap-1.5">
                <Label htmlFor={`${id}-body`}>{t.ai.knowledge.body}</Label>
                <textarea
                    id={`${id}-body`}
                    rows={6}
                    required
                    maxLength={BODY_MAX}
                    dir="auto"
                    value={body}
                    onChange={(event) => setBody(event.target.value)}
                    aria-invalid={errors.body ? true : undefined}
                    aria-describedby={`${id}-body-note`}
                    className="rounded-md border border-input bg-transparent px-3 py-2 text-sm aria-invalid:border-destructive"
                />
                {errors.body ? (
                    <p id={`${id}-body-note`} className="text-xs text-destructive" role="alert">
                        {errors.body}
                    </p>
                ) : (
                    <p id={`${id}-body-note`} className="text-xs text-muted-foreground">
                        {t.ai.knowledge.bodyHelp}
                    </p>
                )}
            </div>
            <FormErrors errors={errors} shown={['title', 'body']} />
            {failed && <p className="text-sm text-destructive">{t.common.error}</p>}
            <div className="flex items-center gap-3">
                <Button type="submit" disabled={busy}>
                    {busy ? t.common.saving : t.ai.knowledge.save}
                </Button>
                <Button type="button" variant="outline" onClick={onDone} disabled={busy}>
                    {t.ai.knowledge.cancel}
                </Button>
            </div>
        </form>
    );
}
