'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import Link from 'next/link';
import { useState } from 'react';

import {
    checklistPasses,
    GoLiveChecklist,
} from '@/components/go-live-checklist';
import { FormErrors } from '@/components/form-errors';
import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { StatusLine } from '@/components/status-dot';
import { Section } from '@/components/section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { WhatsAppConnect } from '@/components/whatsapp-connect';
import type { SignupResult } from '@/components/whatsapp-connect';
import { useI18n } from '@/i18n';
import { getData, sendData } from '@/lib/api';
import { useHref, useTeam } from '@/lib/session';
import type { Margins, Recovery, WhatsApp } from '@/lib/types';
import { useSubmit } from '@/lib/use-form';

const toPercent = (bps: number | null) =>
    bps === null ? '' : String(bps / 100);
const toBps = (percent: string) => Math.round(Number(percent) * 100);

export default function OnboardingPage() {
    const { t } = useI18n();
    const href = useHref();
    const { path, slug, can } = useTeam();
    const client = useQueryClient();

    const recovery = useQuery({
        queryKey: [slug, 'recovery'],
        queryFn: () => getData<Recovery>(path('/recovery')),
        enabled: can('recovery:manage'),
    });

    const whatsapp = useQuery({
        queryKey: [slug, 'whatsapp'],
        queryFn: () => getData<WhatsApp>(path('/whatsapp')),
        enabled: can('integrations:manage'),
    });

    const refreshAll = (data: WhatsApp) => {
        client.setQueryData([slug, 'whatsapp'], data);
        void client.invalidateQueries({ queryKey: [slug, 'recovery'] });
    };

    const templates = useMutation({
        mutationFn: (action: 'submit-library' | 'sync') =>
            sendData<WhatsApp>('POST', path(`/whatsapp/templates/${action}`)),
        onSuccess: refreshAll,
    });

    const completeSignup = async (result: SignupResult) => {
        refreshAll(
            await sendData<WhatsApp>(
                'POST',
                path('/whatsapp/embedded-signup'),
                result,
            ),
        );
    };

    const checklist = recovery.data?.go_live_checklist;

    return (
        <>
            <PageHeader
                title={t.onboarding.title}
                subtitle={t.onboarding.subtitle}
            />

            {can('recovery:manage') && (
                <Section
                    title={t.onboarding.checklist.title}
                    subtitle={t.onboarding.checklist.subtitle}
                >
                    <QueryState query={recovery}>
                        {(data) => (
                            <div className="flex flex-col gap-4">
                                <GoLiveChecklist
                                    items={data.go_live_checklist}
                                />
                                {!data.go_live_checklist.consent_source_ready &&
                                    can('consent:import') && (
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                            className="self-start"
                                        >
                                            <Link
                                                href={href(
                                                    `/t/${slug}/consent`,
                                                )}
                                            >
                                                {
                                                    t.onboarding.checklist
                                                        .goToConsent
                                                }
                                            </Link>
                                        </Button>
                                    )}
                                {checklistPasses(data.go_live_checklist) && (
                                    <div className="flex flex-wrap items-center gap-3">
                                        <span className="text-sm text-gain">
                                            {t.onboarding.checklist.ready}
                                        </span>
                                        <Button asChild size="sm">
                                            <Link
                                                href={href(
                                                    `/t/${slug}/recovery`,
                                                )}
                                            >
                                                {
                                                    t.onboarding.checklist
                                                        .goToRecovery
                                                }
                                            </Link>
                                        </Button>
                                    </div>
                                )}
                            </div>
                        )}
                    </QueryState>
                </Section>
            )}

            {/* The store status comes from the checklist, which needs recovery:manage; without it, say nothing rather than "not connected" (review L4). */}
            {checklist && (
                <Section title={t.onboarding.store.title}>
                    <StatusLine
                        ok={checklist.store_connected}
                        label={
                            checklist.store_connected
                                ? t.onboarding.store.connected
                                : t.onboarding.store.notConnected
                        }
                    />
                    {!checklist.store_connected && (
                        <p className="text-sm text-muted-foreground">
                            {t.onboarding.store.howTo}
                        </p>
                    )}
                </Section>
            )}

            {can('integrations:manage') && (
                <Section title={t.onboarding.whatsapp.title}>
                    <QueryState query={whatsapp}>
                        {(data) => (
                            <div className="flex flex-col gap-4">
                                <StatusLine
                                    ok={data.connected}
                                    label={
                                        data.connected
                                            ? t.onboarding.whatsapp.connected
                                            : t.onboarding.whatsapp.notConnected
                                    }
                                />
                                {data.number && (
                                    <dl className="grid grid-cols-[auto_1fr] gap-x-6 gap-y-1 text-sm">
                                        <dt className="text-muted-foreground">
                                            {t.onboarding.whatsapp.number}
                                        </dt>
                                        <dd
                                            dir="ltr"
                                            className="font-numeric tabular-nums"
                                        >
                                            {data.number.display_phone_number}
                                        </dd>
                                        <dt className="text-muted-foreground">
                                            {t.onboarding.whatsapp.quality}
                                        </dt>
                                        <dd>
                                            {
                                                t.onboarding.whatsapp
                                                    .qualityRating[
                                                    data.number.quality_rating
                                                ]
                                            }
                                        </dd>
                                    </dl>
                                )}
                                {!data.connected && (
                                    <WhatsAppConnect
                                        onComplete={completeSignup}
                                    />
                                )}
                            </div>
                        )}
                    </QueryState>
                </Section>
            )}

            {can('integrations:manage') && whatsapp.data?.connected && (
                <Section
                    title={t.onboarding.templates.title}
                    subtitle={t.onboarding.templates.subtitle}
                    actions={
                        <>
                            {whatsapp.data.templates.length === 0 ? (
                                <Button
                                    size="sm"
                                    disabled={templates.isPending}
                                    onClick={() =>
                                        templates.mutate('submit-library')
                                    }
                                >
                                    {t.onboarding.templates.submit}
                                </Button>
                            ) : (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    disabled={templates.isPending}
                                    onClick={() => templates.mutate('sync')}
                                >
                                    {t.onboarding.templates.sync}
                                </Button>
                            )}
                        </>
                    }
                >
                    {templates.isError && (
                        <p className="text-sm text-destructive">
                            {t.common.error}
                        </p>
                    )}
                    {whatsapp.data.templates.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {t.onboarding.templates.empty}
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {whatsapp.data.templates.map((template) => (
                                <li
                                    key={`${template.name}-${template.language}`}
                                    className="flex items-center justify-between gap-4 py-2"
                                >
                                    <span dir="ltr" className="font-numeric">
                                        {template.name} · {template.language}
                                    </span>
                                    <StatusLine
                                        ok={template.status === 'APPROVED'}
                                        label={
                                            t.onboarding.templates.status[
                                                template.status
                                            ] ?? template.status
                                        }
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>
            )}

            {can('margins:manage') && <MarginsSection />}
        </>
    );
}

function MarginsSection() {
    const { t } = useI18n();
    const { path, slug } = useTeam();
    const client = useQueryClient();
    const margins = useQuery({
        queryKey: [slug, 'margins'],
        queryFn: () => getData<Margins>(path('/margins')),
    });

    return (
        <Section
            title={t.onboarding.margins.title}
            subtitle={t.onboarding.margins.subtitle}
        >
            <QueryState query={margins}>
                {(data) => (
                    <MarginsForm
                        data={data}
                        onSaved={(fresh) => {
                            client.setQueryData([slug, 'margins'], fresh);
                            void client.invalidateQueries({
                                queryKey: [slug, 'recovery'],
                            });
                        }}
                    />
                )}
            </QueryState>
        </Section>
    );
}

function MarginsForm({
    data,
    onSaved,
}: {
    data: Margins;
    onSaved: (fresh: Margins) => void;
}) {
    const { t } = useI18n();
    const { path } = useTeam();
    const { busy, errors, failed, run } = useSubmit();
    const [saved, setSaved] = useState(false);
    const assumed = data.default.source === 'assumed';
    const [defaultPercent, setDefaultPercent] = useState(
        assumed ? '' : toPercent(data.default.margin_bps),
    );
    const [categories, setCategories] = useState(
        Object.fromEntries(
            data.categories.map((category) => [
                category.external_id,
                toPercent(category.margin_bps),
            ]),
        ),
    );

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        setSaved(false);
        const saved_bps = (id: string) =>
            data.categories.find((category) => category.external_id === id)?.margin_bps ?? null;

        void run(async () => {
            const fresh = await sendData<Margins>('PUT', path('/margins'), {
                default_margin_bps:
                    defaultPercent === '' ? null : toBps(defaultPercent),
                // A cleared field that had a margin sends null: back to the default (review H16).
                categories: Object.entries(categories)
                    .filter(([id, percent]) => percent !== '' || saved_bps(id) !== null)
                    .map(([external_id, percent]) => ({
                        external_id,
                        margin_bps: percent === '' ? null : toBps(percent),
                    })),
            });
            onSaved(fresh);
            setSaved(true);
        });
    };

    return (
        <form
            onSubmit={submit}
            className="flex flex-col gap-4"
            data-testid="margins-form"
        >
            <PercentRow
                id="default_margin"
                label={t.onboarding.margins.default}
                help={
                    assumed
                        ? `${t.onboarding.margins.assumed}: ${toPercent(data.default.margin_bps)}%`
                        : t.onboarding.margins.defaultHelp
                }
                value={defaultPercent}
                onChange={setDefaultPercent}
                error={errors.default_margin_bps}
            />
            {data.categories.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t.onboarding.margins.noCategories}
                </p>
            ) : (
                <div className="grid gap-3 sm:grid-cols-2">
                    {data.categories.map((category) => (
                        <PercentRow
                            key={category.external_id}
                            id={`category-${category.external_id}`}
                            label={category.name}
                            value={categories[category.external_id] ?? ''}
                            onChange={(value) =>
                                setCategories({
                                    ...categories,
                                    [category.external_id]: value,
                                })
                            }
                        />
                    ))}
                </div>
            )}
            <FormErrors errors={errors} shown={['default_margin_bps']} />
            {failed && (
                <p className="text-sm text-destructive">{t.common.error}</p>
            )}
            <div className="flex items-center gap-3">
                <Button type="submit" disabled={busy}>
                    {busy ? t.common.saving : t.common.save}
                </Button>
                {saved && (
                    <span className="text-sm text-gain">{t.common.saved}</span>
                )}
            </div>
        </form>
    );
}

function PercentRow({
    id,
    label,
    help,
    value,
    onChange,
    error,
}: {
    id: string;
    label: string;
    help?: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
}) {
    return (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            <div className="flex items-center gap-2">
                <Input
                    id={id}
                    type="number"
                    min={0}
                    max={100}
                    step={0.5}
                    dir="ltr"
                    className="w-28 font-numeric tabular-nums"
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    aria-invalid={error ? true : undefined}
                />
                <span className="text-sm text-muted-foreground">%</span>
            </div>
            {help && <p className="text-xs text-muted-foreground">{help}</p>}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}
