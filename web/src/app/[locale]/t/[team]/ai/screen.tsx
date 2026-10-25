'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { OctagonX, TriangleAlert } from 'lucide-react';
import { useState } from 'react';

import { ConfirmButton } from '@/components/confirm-button';
import { FormErrors } from '@/components/form-errors';
import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { Section } from '@/components/section';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { format, useI18n } from '@/i18n';
import { ApiError, getData, sendData } from '@/lib/api';
import { useTeam } from '@/lib/session';
import type { AgentMode, AgentSettings } from '@/lib/types';
import { useSubmit } from '@/lib/use-form';
import { cn } from '@/lib/utils';

const MODES: AgentMode[] = ['off', 'assisted', 'scoped_autonomous'];

const count = new Intl.NumberFormat('en-US');
const usd = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 4 });
const percent = new Intl.NumberFormat('en-US', { style: 'percent', maximumFractionDigits: 1 });

/** Rates come as fractions (0.0123); null when there is nothing to measure yet. */
const rate = (value: number | null) => (value === null ? '—' : percent.format(value));

export default function AiSettingsPage() {
    const { t } = useI18n();
    const { path, slug } = useTeam();
    const client = useQueryClient();
    const settings = useQuery({
        queryKey: [slug, 'ai-settings'],
        queryFn: () => getData<AgentSettings>(path('/ai/settings')),
    });
    const update = (fresh: AgentSettings) => client.setQueryData([slug, 'ai-settings'], fresh);

    return (
        <>
            <PageHeader title={t.ai.settings.title} subtitle={t.ai.settings.subtitle} />
            <QueryState query={settings}>
                {(data) => (
                    <>
                        <ModeSection settings={data} onSaved={update} />
                        <LimitsForm settings={data} onSaved={update} />
                        <QualitySection settings={data} />
                    </>
                )}
            </QueryState>
        </>
    );
}

function ModeSection({ settings, onSaved }: { settings: AgentSettings; onSaved: (fresh: AgentSettings) => void }) {
    const { t } = useI18n();
    const { path } = useTeam();
    const s = t.ai.settings;
    const [choosingAutonomy, setChoosingAutonomy] = useState(false);
    const [confirmation, setConfirmation] = useState('');
    const locked = settings.autonomy_blockers.length > 0;

    const setMode = useMutation({
        mutationFn: (mode: AgentMode) =>
            sendData<AgentSettings>('PUT', path('/ai/settings'), {
                mode,
                ...(mode === 'scoped_autonomous' ? { confirmation } : {}),
            }),
        onSuccess: (fresh) => {
            onSaved(fresh);
            setChoosingAutonomy(false);
            setConfirmation('');
        },
    });

    const stop = useMutation({
        mutationFn: () => sendData<AgentSettings>('POST', path('/ai/stop')),
        onSuccess: (fresh) => {
            onSaved(fresh);
            setChoosingAutonomy(false);
        },
    });

    // A blocked switch to autonomy answers 422 with the blocker codes under `mode`.
    const modeErrors =
        setMode.error instanceof ApiError && setMode.error.status === 422
            ? Object.values(setMode.error.errors).flat()
            : [];
    const blockerLabel = (code: string) => (code in s.blocker ? s.blocker[code as keyof typeof s.blocker] : code);

    const choose = (mode: AgentMode) => {
        setMode.reset();

        if (mode === 'scoped_autonomous') {
            setChoosingAutonomy(true);

            return;
        }

        setChoosingAutonomy(false);

        if (mode !== settings.mode) {
            setMode.mutate(mode);
        }
    };

    const selected: AgentMode = choosingAutonomy ? 'scoped_autonomous' : settings.mode;

    return (
        <Section
            title={s.mode}
            testId="ai-mode"
            actions={
                settings.mode !== 'off' && (
                    <ConfirmButton
                        variant="destructive"
                        message={s.stopConfirm}
                        onConfirm={() => stop.mutate()}
                        disabled={stop.isPending}
                        data-testid="ai-stop"
                    >
                        <OctagonX />
                        {s.stop}
                    </ConfirmButton>
                )
            }
        >
            {settings.auto_downgraded_at && (
                <Alert>
                    <TriangleAlert />
                    <AlertDescription>{s.downgraded}</AlertDescription>
                </Alert>
            )}

            <div role="radiogroup" aria-label={s.mode} className="flex flex-col gap-2">
                {MODES.map((mode) => {
                    const disabled = (mode === 'scoped_autonomous' && locked) || setMode.isPending;

                    return (
                        <button
                            key={mode}
                            type="button"
                            role="radio"
                            aria-checked={selected === mode}
                            data-mode={mode}
                            disabled={disabled}
                            onClick={() => choose(mode)}
                            className={cn(
                                'flex items-center gap-3 rounded-md border px-4 py-3 text-start text-sm transition-colors',
                                'disabled:cursor-not-allowed disabled:opacity-50',
                                selected === mode ? 'border-primary bg-primary/10 font-medium' : 'hover:bg-muted',
                            )}
                        >
                            <span
                                aria-hidden
                                className={cn(
                                    'size-4 shrink-0 rounded-full border-2',
                                    selected === mode ? 'border-primary bg-primary' : 'border-muted-foreground',
                                )}
                            />
                            {s.modes[mode]}
                        </button>
                    );
                })}
            </div>

            {locked && (
                <div className="flex flex-col gap-2 rounded-md border border-dashed p-4 text-sm" data-testid="ai-blockers">
                    <p className="font-medium">{s.autonomyLocked}</p>
                    <ul className="flex list-disc flex-col gap-1 ps-5 text-muted-foreground">
                        {settings.autonomy_blockers.map((code) => (
                            <li key={code}>{blockerLabel(code)}</li>
                        ))}
                    </ul>
                    <p className="font-numeric tabular-nums text-muted-foreground">
                        {format(s.assistedProgress, {
                            days: settings.assisted_days,
                            required: settings.assisted_days_required,
                        })}
                    </p>
                </div>
            )}

            {choosingAutonomy && !locked && (
                <form
                    className="flex flex-col gap-3 rounded-md border p-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        setMode.mutate('scoped_autonomous');
                    }}
                >
                    <Label htmlFor="autonomy_confirmation">{s.confirmLabel}</Label>
                    <p className="rounded-md bg-muted px-3 py-2 text-sm font-medium" dir="auto">
                        {settings.autonomy_confirmation}
                    </p>
                    <Input
                        id="autonomy_confirmation"
                        dir="auto"
                        required
                        autoComplete="off"
                        value={confirmation}
                        onChange={(event) => setConfirmation(event.target.value)}
                    />
                    <div className="flex items-center gap-3">
                        <Button
                            type="submit"
                            disabled={setMode.isPending || confirmation.trim() !== settings.autonomy_confirmation}
                        >
                            {setMode.isPending ? t.common.saving : s.save}
                        </Button>
                        <Button type="button" variant="outline" onClick={() => setChoosingAutonomy(false)}>
                            {t.common.cancel}
                        </Button>
                    </div>
                </form>
            )}

            {modeErrors.length > 0 && (
                <div className="flex flex-col gap-1 text-sm text-destructive" role="alert">
                    <p>{s.autonomyLocked}</p>
                    <ul className="flex list-disc flex-col gap-1 ps-5">
                        {modeErrors.map((code) => (
                            <li key={code}>{blockerLabel(code)}</li>
                        ))}
                    </ul>
                </div>
            )}
            {((setMode.isError && modeErrors.length === 0) || stop.isError) && (
                <p className="text-sm text-destructive">{t.common.error}</p>
            )}
        </Section>
    );
}

function LimitsForm({ settings, onSaved }: { settings: AgentSettings; onSaved: (fresh: AgentSettings) => void }) {
    const { t } = useI18n();
    const { path } = useTeam();
    const s = t.ai.settings;
    const { busy, errors, failed, run } = useSubmit();
    const [saved, setSaved] = useState(false);
    const [cap, setCap] = useState(String(settings.daily_cap_usd));
    const [handoff, setHandoff] = useState(settings.handoff_cart_sar === null ? '' : String(settings.handoff_cart_sar));

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        setSaved(false);

        void run(async () => {
            const payload: Record<string, number> = { daily_cap_usd: Number(cap) };

            // Left empty means "no cart threshold": the API has no way to clear it, so don't send it.
            if (handoff !== '') {
                payload.handoff_cart_sar = Math.round(Number(handoff));
            }

            onSaved(await sendData<AgentSettings>('PUT', path('/ai/settings'), payload));
            setSaved(true);
        });
    };

    return (
        <form onSubmit={submit}>
            <Section title={s.limits} testId="ai-limits">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="daily_cap_usd">{s.cap}</Label>
                        <Input
                            id="daily_cap_usd"
                            type="number"
                            dir="ltr"
                            step="0.01"
                            min={0.1}
                            max={settings.max_daily_cap_usd}
                            required
                            className="w-40 font-numeric tabular-nums"
                            value={cap}
                            onChange={(event) => setCap(event.target.value)}
                            aria-invalid={errors.daily_cap_usd ? true : undefined}
                            aria-describedby="daily_cap_usd-note"
                        />
                        {errors.daily_cap_usd ? (
                            <p id="daily_cap_usd-note" className="text-xs text-destructive" role="alert">
                                {errors.daily_cap_usd}
                            </p>
                        ) : (
                            <p id="daily_cap_usd-note" className="text-xs text-muted-foreground">
                                {format(s.capHelp, { max: settings.max_daily_cap_usd })}
                            </p>
                        )}
                        <p className="text-sm text-muted-foreground" data-testid="ai-spent">
                            {s.spentToday}:{' '}
                            <span className="font-numeric tabular-nums text-foreground" dir="ltr">
                                {usd.format(settings.spent_today_usd)} / {usd.format(settings.daily_cap_usd)}
                            </span>
                        </p>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="handoff_cart_sar">{s.handoffCart}</Label>
                        <Input
                            id="handoff_cart_sar"
                            type="number"
                            dir="ltr"
                            step="1"
                            min={1}
                            max={1000000}
                            className="w-40 font-numeric tabular-nums"
                            value={handoff}
                            onChange={(event) => setHandoff(event.target.value)}
                            aria-invalid={errors.handoff_cart_sar ? true : undefined}
                        />
                        {errors.handoff_cart_sar && (
                            <p className="text-xs text-destructive" role="alert">
                                {errors.handoff_cart_sar}
                            </p>
                        )}
                    </div>
                </div>
                <FormErrors errors={errors} shown={['daily_cap_usd', 'handoff_cart_sar']} />
                {failed && <p className="text-sm text-destructive">{t.common.error}</p>}
                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={busy}>
                        {busy ? t.common.saving : s.save}
                    </Button>
                    {saved && <span className="text-sm text-gain">{s.saved}</span>}
                </div>
            </Section>
        </form>
    );
}

function QualitySection({ settings }: { settings: AgentSettings }) {
    const { t } = useI18n();
    const s = t.ai.settings;
    const { draft_quality: quality, validator } = settings;

    const stat = (label: string, value: string, testId?: string) => (
        <div className="flex flex-col gap-1 rounded-md border p-3" data-testid={testId}>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="font-numeric text-lg font-semibold tabular-nums" dir="ltr">
                {value}
            </dd>
        </div>
    );

    return (
        <Section title={s.quality} testId="ai-quality">
            <dl className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                {stat(s.approved, count.format(quality.approved))}
                {stat(s.edited, count.format(quality.edited))}
                {stat(s.rejected, count.format(quality.rejected))}
                {stat(s.editRate, rate(quality.edit_rate), 'ai-edit-rate')}
            </dl>
            <h3 className="text-sm font-semibold">{s.validator}</h3>
            <dl className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                {stat(s.runs, count.format(validator.runs))}
                {stat(t.ai.blocked, count.format(validator.blocked))}
                {stat(s.blockRate, rate(validator.rate), 'ai-block-rate')}
            </dl>
            <p className="text-sm text-muted-foreground">
                {s.model}:{' '}
                <span className="font-mono text-foreground" dir="ltr">
                    {settings.model}
                </span>
            </p>
        </Section>
    );
}
