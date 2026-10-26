'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Pause, Power } from 'lucide-react';
import { useState } from 'react';

import { ConfirmButton } from '@/components/confirm-button';
import { FormErrors } from '@/components/form-errors';
import { checklistPasses, GoLiveChecklist } from '@/components/go-live-checklist';
import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { Section } from '@/components/section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { format, useI18n } from '@/i18n';
import type { Dictionary } from '@/i18n/ar';
import { ApiError, getData, sendData } from '@/lib/api';
import { toMajorInput, toMinor } from '@/lib/money';
import { useTeam } from '@/lib/session';
import type { Recovery, RecoverySettings } from '@/lib/types';
import { useSubmit } from '@/lib/use-form';
import { cn } from '@/lib/utils';

type NumericKey = {
    [K in keyof RecoverySettings]: RecoverySettings[K] extends number ? K : never;
}[keyof RecoverySettings];

type NumberSpec = {
    key: NumericKey;
    label: keyof Dictionary['recovery'];
    /** Stored value = shown value × scale (basis points). */
    scale?: number;
    /** Minor units of the workspace currency: shown in major units, scale 10^exponent (ADR-012). */
    money?: boolean;
    min: number;
    max?: number;
    help?: keyof Dictionary['recovery'];
};

// Bounds mirror RecoveryController::update (US-E05-06).
const TIMING: NumberSpec[] = [
    { key: 'first_delay_minutes', label: 'firstDelay', min: 60, max: 4320 },
    { key: 'second_delay_minutes', label: 'secondDelay', min: 60, max: 10080 },
    { key: 'max_messages', label: 'maxMessages', min: 1, max: 3 },
    { key: 'min_cart_halalas', label: 'minCart', money: true, min: 0 },
    { key: 'frequency_days', label: 'frequency', min: 1, max: 60 },
];

const DISCOUNT: NumberSpec[] = [
    { key: 'discount_percent', label: 'discountPercent', min: 0, max: 50 },
    { key: 'discount_cap_percent', label: 'discountCap', min: 0, max: 50 },
    { key: 'margin_floor_bps', label: 'marginFloor', scale: 100, min: 0, max: 100 },
    { key: 'monthly_discount_budget_halalas', label: 'budget', money: true, min: 1, help: 'budgetHelp' },
    { key: 'approval_window_minutes', label: 'approvalWindow', min: 15, max: 2880 },
];

export default function RecoveryPage() {
    const { t } = useI18n();
    const { path, slug } = useTeam();
    const client = useQueryClient();
    const recovery = useQuery({
        queryKey: [slug, 'recovery'],
        queryFn: () => getData<Recovery>(path('/recovery')),
    });

    const toggle = useMutation({
        mutationFn: (action: 'enable' | 'pause') => sendData<Recovery>('POST', path(`/recovery/${action}`)),
        onSuccess: (data) => client.setQueryData([slug, 'recovery'], data),
    });

    return (
        <>
            <PageHeader title={t.recovery.title} />
            <QueryState query={recovery}>
                {(data) => {
                    const ready = checklistPasses(data.go_live_checklist);
                    const on = data.settings.enabled;

                    return (
                        <>
                            <Section
                                title={t.onboarding.checklist.title}
                                testId="recovery-status"
                                actions={
                                    <span
                                        data-testid="recovery-state"
                                        data-enabled={on}
                                        className={cn(
                                            'rounded-full border px-3 py-1 text-sm font-medium',
                                            on ? 'border-gain text-gain' : 'border-muted-foreground text-muted-foreground',
                                        )}
                                    >
                                        {on ? `● ${t.recovery.statusOn}` : `○ ${t.recovery.statusOff}`}
                                    </span>
                                }
                            >
                                <GoLiveChecklist items={data.go_live_checklist} />
                                <div className="flex flex-wrap items-center gap-3">
                                    {on ? (
                                        <ConfirmButton
                                            variant="destructive"
                                            message={t.recovery.pauseConfirm}
                                            onConfirm={() => toggle.mutate('pause')}
                                            disabled={toggle.isPending}
                                        >
                                            <Pause />
                                            {t.recovery.pause}
                                        </ConfirmButton>
                                    ) : (
                                        <Button
                                            onClick={() => toggle.mutate('enable')}
                                            disabled={!ready || toggle.isPending}
                                        >
                                            <Power />
                                            {t.recovery.enable}
                                        </Button>
                                    )}
                                    {!on && !ready && (
                                        <span className="text-sm text-muted-foreground">{t.recovery.enableBlocked}</span>
                                    )}
                                    {toggle.isError && (
                                        <span className="text-sm text-destructive">
                                            {toggle.error instanceof ApiError && toggle.error.status === 422
                                                ? t.recovery.enableBlocked
                                                : t.common.error}
                                        </span>
                                    )}
                                </div>
                            </Section>
                            <SettingsForm
                                settings={data.settings}
                                onSaved={(fresh) => client.setQueryData([slug, 'recovery'], fresh)}
                            />
                        </>
                    );
                }}
            </QueryState>
        </>
    );
}

function SettingsForm({ settings, onSaved }: { settings: RecoverySettings; onSaved: (fresh: Recovery) => void }) {
    const { t, locale } = useI18n();
    const { path, currency } = useTeam();
    const { busy, errors, failed, run } = useSubmit();
    const [saved, setSaved] = useState(false);
    const [values, setValues] = useState<Record<string, string>>(() =>
        Object.fromEntries(
            [...TIMING, ...DISCOUNT].map((spec) => [
                spec.key,
                spec.money ? toMajorInput(settings[spec.key], currency) : String(settings[spec.key] / (spec.scale ?? 1)),
            ]),
        ),
    );
    const symbol = locale === 'ar' ? currency.symbol_ar : currency.symbol_en;
    const [discountEnabled, setDiscountEnabled] = useState(settings.discount_enabled);
    const [discountCode, setDiscountCode] = useState(settings.discount_code ?? '');

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        setSaved(false);

        void run(async () => {
            const payload: Record<string, unknown> = {
                discount_enabled: discountEnabled,
                discount_code: discountCode === '' ? null : discountCode,
            };

            for (const spec of [...TIMING, ...DISCOUNT]) {
                // Money in exact minor units ("2500.50" EGP → 250050); a malformed amount is left for the API to reject.
                payload[spec.key] = spec.money
                    ? (toMinor(values[spec.key], currency) ?? values[spec.key])
                    : Math.round(Number(values[spec.key]) * (spec.scale ?? 1));
            }

            onSaved(await sendData<Recovery>('PUT', path('/recovery'), payload));
            setSaved(true);
        });
    };

    const numberField = (spec: NumberSpec) => (
        <div key={spec.key} className="flex flex-col gap-1.5">
            <Label htmlFor={spec.key}>{format(t.recovery[spec.label], { currency: symbol })}</Label>
            <Input
                id={spec.key}
                type="number"
                dir="ltr"
                min={spec.min}
                max={spec.max}
                step={spec.money ? 1 / 10 ** currency.exponent : undefined}
                className="w-40 font-numeric tabular-nums"
                // An empty field would be sent as 0, which can switch a protection off (review B6).
                required
                value={values[spec.key]}
                onChange={(event) => setValues({ ...values, [spec.key]: event.target.value })}
                aria-invalid={errors[spec.key] ? true : undefined}
                aria-describedby={errors[spec.key] || spec.help ? `${spec.key}-note` : undefined}
            />
            {spec.help && !errors[spec.key] && <p id={`${spec.key}-note`} className="text-xs text-muted-foreground">{t.recovery[spec.help]}</p>}
            {errors[spec.key] && <p id={`${spec.key}-note`} className="text-xs text-destructive" role="alert">{errors[spec.key]}</p>}
        </div>
    );

    return (
        <form onSubmit={submit} className="flex flex-col gap-6" data-testid="recovery-settings">
            <Section title={t.recovery.timing} subtitle={t.recovery.boundsHelp}>
                <div className="grid gap-4 sm:grid-cols-2">{TIMING.map(numberField)}</div>
            </Section>

            <Section title={t.recovery.discount}>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={discountEnabled}
                        onChange={(event) => setDiscountEnabled(event.target.checked)}
                        className="size-4 accent-primary"
                    />
                    {t.recovery.discountEnabled}
                </label>
                {discountEnabled && (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {DISCOUNT.map(numberField)}
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="discount_code">{t.recovery.discountCode}</Label>
                            <Input
                                id="discount_code"
                                dir="ltr"
                                className="w-40 font-numeric"
                                required
                                value={discountCode}
                                onChange={(event) => setDiscountCode(event.target.value)}
                                aria-invalid={errors.discount_code ? true : undefined}
                            />
                            {errors.discount_code && <p className="text-xs text-destructive">{errors.discount_code}</p>}
                        </div>
                    </div>
                )}
            </Section>

            <FormErrors errors={errors} shown={discountEnabled ? [...TIMING, ...DISCOUNT].map((spec) => spec.key as string).concat('discount_code') : TIMING.map((spec) => spec.key)} />
            {failed && <p className="text-sm text-destructive">{t.common.error}</p>}
            <div className="flex items-center gap-3">
                <Button type="submit" disabled={busy}>
                    {busy ? t.common.saving : t.common.save}
                </Button>
                {saved && <span className="text-sm text-gain">{t.common.saved}</span>}
            </div>
        </form>
    );
}
