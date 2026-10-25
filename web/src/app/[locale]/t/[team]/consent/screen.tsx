'use client';

import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { Section } from '@/components/section';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n';
import { getData, sendData } from '@/lib/api';
import { useTeam } from '@/lib/session';
import { formatDateTime } from '@/lib/time';
import type { ConsentImportSummary } from '@/lib/types';
import { useSubmit } from '@/lib/use-form';

const VIA = ['checkout', 'website_form', 'in_store', 'whatsapp', 'other'] as const;
const count = new Intl.NumberFormat('en-US');

/**
 * The merchant's consent source (review B1): a CSV of customers who agreed to
 * WhatsApp marketing, with the wording and the owner's attestation.
 */
export default function ConsentScreen() {
    const { t, locale } = useI18n();
    const { path, slug } = useTeam();
    const client = useQueryClient();
    const { busy, errors, failed, run } = useSubmit();
    const [file, setFile] = useState<File | null>(null);
    const [via, setVia] = useState<(typeof VIA)[number]>('checkout');
    const [text, setText] = useState(t.consent.consentTextDefault);
    const [attested, setAttested] = useState(false);
    const [result, setResult] = useState<ConsentImportSummary | null>(null);
    const key = [slug, 'consent-imports'];

    const history = useQuery({ queryKey: key, queryFn: () => getData<ConsentImportSummary[]>(path('/consent/imports')) });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = event.currentTarget;
        setResult(null);

        void run(async () => {
            const body = new FormData();
            body.append('file', file as File);
            body.append('collected_via', via);
            body.append('consent_text', text);
            body.append('attested', attested ? '1' : '0');

            setResult(await sendData<ConsentImportSummary>('POST', path('/consent/imports'), body));
            setFile(null);
            setAttested(false);
            form.reset();
            void client.invalidateQueries({ queryKey: key });
            // The go-live checklist depends on having a consent source.
            void client.invalidateQueries({ queryKey: [slug, 'recovery'] });
        });
    };

    return (
        <>
            <PageHeader title={t.consent.title} subtitle={t.consent.subtitle} />

            <Section title={t.consent.submit} subtitle={t.consent.optedOutNote}>
                <form onSubmit={submit} className="flex max-w-xl flex-col gap-5" data-testid="consent-import">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="consent_file">{t.consent.file}</Label>
                        <input
                            id="consent_file"
                            type="file"
                            accept=".csv,text/csv"
                            required
                            onChange={(event) => setFile(event.target.files?.[0] ?? null)}
                            className="text-sm file:me-3 file:rounded-md file:border file:border-border-strong file:bg-transparent file:px-3 file:py-1.5 file:text-sm"
                        />
                        <p className="text-xs text-muted-foreground">{t.consent.fileHelp}</p>
                        {errors.file && <p className="text-xs text-destructive">{errors.file}</p>}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="collected_via">{t.consent.collectedVia}</Label>
                        <select
                            id="collected_via"
                            value={via}
                            onChange={(event) => setVia(event.target.value as (typeof VIA)[number])}
                            className="h-9 w-64 rounded-md border border-input bg-transparent px-2 text-sm"
                        >
                            {VIA.map((option) => (
                                <option key={option} value={option}>
                                    {t.consent.via[option]}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="consent_text">{t.consent.consentText}</Label>
                        <textarea
                            id="consent_text"
                            rows={3}
                            required
                            minLength={20}
                            maxLength={2000}
                            dir="auto"
                            value={text}
                            onChange={(event) => setText(event.target.value)}
                            className="rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                        <p className="text-xs text-muted-foreground">{t.consent.consentTextHelp}</p>
                        {errors.consent_text && <p className="text-xs text-destructive">{errors.consent_text}</p>}
                    </div>

                    <label className="flex items-start gap-3 rounded-md border border-border-strong p-3 text-sm">
                        <input
                            type="checkbox"
                            required
                            checked={attested}
                            onChange={(event) => setAttested(event.target.checked)}
                            className="mt-0.5 size-4 shrink-0 accent-primary"
                        />
                        <span>{t.consent.attestation}</span>
                    </label>
                    {errors.attested && <p className="text-xs text-destructive">{errors.attested}</p>}

                    {failed && <p className="text-sm text-destructive">{t.common.error}</p>}
                    <Button type="submit" className="self-start" disabled={busy || file === null || !attested}>
                        {t.consent.submit}
                    </Button>
                </form>

                {result && (
                    <div className="flex flex-col gap-2" role="status" data-testid="consent-result">
                        <span className="text-sm font-medium text-gain">{t.consent.result}</span>
                        <Summary item={result} />
                    </div>
                )}
            </Section>

            <Section title={t.consent.history}>
                <QueryState query={history}>
                    {(items) =>
                        items.length === 0 ? (
                            <p className="text-sm text-muted-foreground">{t.consent.historyEmpty}</p>
                        ) : (
                            <ul className="divide-y">
                                {items.map((item) => (
                                    <li key={item.id} className="flex flex-col gap-2 py-3">
                                        <span className="text-xs text-muted-foreground">
                                            {item.created_at ? formatDateTime(item.created_at, locale) : ''}
                                            {item.collected_via ? ` · ${t.consent.via[item.collected_via] ?? item.collected_via}` : ''}
                                        </span>
                                        <Summary item={item} />
                                    </li>
                                ))}
                            </ul>
                        )
                    }
                </QueryState>
            </Section>
        </>
    );
}

function Summary({ item }: { item: ConsentImportSummary }) {
    const { t } = useI18n();
    const rows: [string, number][] = [
        [t.consent.granted, item.granted],
        [t.consent.alreadyGranted, item.already_granted],
        [t.consent.optedOut, item.skipped_opted_out],
        [t.consent.invalid, item.skipped_invalid],
    ];

    return (
        <dl className="grid grid-cols-2 gap-x-6 gap-y-1 text-sm sm:grid-cols-4">
            {rows.map(([label, value]) => (
                <div key={label} className="flex flex-col">
                    <dt className="text-xs text-muted-foreground">{label}</dt>
                    <dd className="font-numeric tabular-nums">{count.format(value)}</dd>
                </div>
            ))}
        </dl>
    );
}
