'use client';

import { useQuery } from '@tanstack/react-query';
import { Snowflake, TriangleAlert } from 'lucide-react';
import Link from 'next/link';
import { useState } from 'react';

import { KpiCard } from '@/components/brand/kpi-card';
import { RangeEstimate } from '@/components/brand/range-estimate';
import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { Section } from '@/components/section';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { format, useI18n } from '@/i18n';
import { getData } from '@/lib/api';
import { formatSar } from '@/lib/money';
import { useHref, useTeam } from '@/lib/session';
import { formatDate } from '@/lib/time';
import type { Interval, ProfitReport, Snapshot } from '@/lib/types';
import { cn } from '@/lib/utils';

const isoDay = (date: Date) => date.toISOString().slice(0, 10);

function defaultPeriod() {
    const to = new Date();
    const from = new Date(to.getTime() - 28 * 86_400_000);

    return { from: isoDay(from), to: isoDay(to) };
}

const percent = (value: number | null) => (value === null ? '—' : `${(value * 100).toFixed(1)}%`);

export default function ReportPage() {
    const { t } = useI18n();
    const { path, slug, can } = useTeam();
    const [draft, setDraft] = useState(defaultPeriod);
    const [period, setPeriod] = useState(draft);

    const report = useQuery({
        queryKey: [slug, 'report', period],
        queryFn: () => getData<ProfitReport>(path(`/reports/profit?from=${period.from}&to=${period.to}`)),
    });

    const snapshots = useQuery({
        queryKey: [slug, 'report-snapshots'],
        queryFn: () => getData<Snapshot[]>(path('/reports/profit/snapshots')),
    });

    return (
        <>
            <PageHeader title={t.report.title} subtitle={t.report.subtitle} />

            <form
                className="flex flex-wrap items-end gap-3"
                onSubmit={(event) => {
                    event.preventDefault();
                    setPeriod(draft);
                }}
            >
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="from">{t.report.from}</Label>
                    <Input id="from" type="date" dir="ltr" className="font-numeric" value={draft.from} max={draft.to} onChange={(event) => setDraft({ ...draft, from: event.target.value })} />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="to">{t.report.to}</Label>
                    <Input id="to" type="date" dir="ltr" className="font-numeric" value={draft.to} min={draft.from} onChange={(event) => setDraft({ ...draft, to: event.target.value })} />
                </div>
                <Button type="submit" variant="outline">
                    {t.report.apply}
                </Button>
            </form>

            <QueryState query={report}>
                {(data) => <ReportBody report={data} canEditMargins={can('margins:manage')} />}
            </QueryState>

            <Section title={t.report.snapshots}>
                <QueryState query={snapshots}>
                    {(items) => (items.length === 0 ? <p className="text-sm text-muted-foreground">{t.report.snapshotsEmpty}</p> : <SnapshotList items={items} />)}
                </QueryState>
            </Section>
        </>
    );
}

function ReportBody({ report, canEditMargins }: { report: ProfitReport; canEditMargins: boolean }) {
    const { t, locale } = useI18n();
    const href = useHref();
    const { slug } = useTeam();

    if (report.status === 'frozen') {
        return (
            <Alert data-testid="report-frozen">
                <Snowflake />
                <AlertTitle>{t.report.frozenTitle}</AlertTitle>
                <AlertDescription>{t.report.frozenBody}</AlertDescription>
            </Alert>
        );
    }

    const profit = report.incremental_profit_halalas;

    return (
        <div className="flex flex-col gap-6" data-testid="report">
            <p className={cn('text-lg font-medium', report.verdict === 'harmful' && 'text-loss')} data-testid="report-verdict">
                {t.report.verdict[report.verdict]}{' '}
                {report.verdict === 'inconclusive' && report.additional_customers_needed > 0 && (
                    <span className="text-base font-normal text-muted-foreground">
                        {format(t.report.needMore, { n: report.additional_customers_needed })}
                    </span>
                )}
            </p>

            {report.margin_quality.warning && (
                <Alert>
                    <TriangleAlert />
                    <AlertDescription className="flex flex-wrap items-center gap-3">
                        {t.report.marginWarning}
                        {canEditMargins && (
                            <Link href={href(`/t/${slug}/onboarding`)} className="text-link hover:underline">
                                {t.report.enterMargins}
                            </Link>
                        )}
                    </AlertDescription>
                </Alert>
            )}

            <div className="grid gap-4 md:grid-cols-2">
                {/* 4. The headline: the only gold figure on this screen. */}
                <Estimate label={t.report.incrementalProfit} interval={profit} isMoney />
                {/* 2. Honest revenue, as a range too. */}
                <Estimate label={t.report.incrementalRevenue} interval={report.incremental_revenue_halalas} />
                {/* 1. What other tools would claim. Plain ink, never gold. */}
                <KpiCard
                    label={t.report.attributed}
                    value={formatSar(report.attributed_revenue_halalas, locale)}
                    footer={<p className="text-[13px] text-muted-foreground">{t.report.attributedHelp}</p>}
                />
                {/* 3. What it cost. */}
                <KpiCard
                    label={t.report.costs}
                    value={formatSar(report.costs_halalas.total, locale)}
                    footer={
                        <ul className="flex flex-col gap-0.5 text-[13px] text-muted-foreground">
                            {Object.entries(report.costs_halalas)
                                .filter(([kind]) => kind !== 'total')
                                .map(([kind, amount]) => (
                                    <li key={kind} className="flex justify-between gap-4">
                                        <span>{t.report.costKind[kind] ?? kind}</span>
                                        <span className="font-numeric tabular-nums">
                                            {formatSar(amount, locale)}
                                        </span>
                                    </li>
                                ))}
                        </ul>
                    }
                />
            </div>

            <div className="flex flex-col gap-1 text-sm text-muted-foreground">
                <p>
                    {format(t.report.sample, {
                        t: report.sample.treatment,
                        c: report.sample.control,
                        m: report.sample.not_yet_matured,
                    })}
                </p>
                <p>
                    {format(t.report.conversion, {
                        t: percent(report.conversion.treatment),
                        c: percent(report.conversion.control),
                    })}
                </p>
                <p className="text-xs">
                    {t.report.method}: <span className="font-numeric">{report.method_version}</span>
                </p>
            </div>
        </div>
    );
}

/** A range with its probability, or an honest "not enough data" — never a lone number. */
function Estimate({ label, interval, isMoney = false }: { label: string; interval: Interval; isMoney?: boolean }) {
    const { t, locale } = useI18n();

    if (interval.low === null || interval.high === null || interval.win_probability === null) {
        return (
            <div className="flex flex-col gap-3 rounded-xl rounded-ss-none border bg-card p-6 shadow-card">
                <span className="font-numeric text-xs font-medium text-muted-foreground">{label}</span>
                <span className="text-sm text-muted-foreground">{t.report.noRange}</span>
            </div>
        );
    }

    return (
        <RangeEstimate
            label={label}
            low={interval.low}
            high={interval.high}
            winProbability={interval.win_probability}
            verdict={interval.verdict}
            locale={locale}
            isMoney={isMoney}
        />
    );
}

function SnapshotList({ items }: { items: Snapshot[] }) {
    const { t, locale } = useI18n();

    return (
        <ul className="divide-y text-sm" data-testid="report-snapshots">
            {items.map((snapshot) => {
                const profit = snapshot.report.status === 'ok' ? snapshot.report.incremental_profit_halalas : null;

                return (
                    <li key={snapshot.period_start} className="flex flex-wrap items-center justify-between gap-3 py-3">
                        <span>
                            {formatDate(snapshot.period_start, locale)} – {formatDate(snapshot.period_end, locale)}
                        </span>
                        {snapshot.report.status === 'frozen' ? (
                            <span className="text-muted-foreground">{t.report.frozenTitle}</span>
                        ) : profit && profit.low !== null && profit.high !== null ? (
                            <span className="flex items-center gap-3">
                                <span dir="ltr" className="font-numeric tabular-nums">
                                    {formatSar(profit.low, locale)} – {formatSar(profit.high, locale)}
                                </span>
                                <span className="text-muted-foreground">{t.report.verdict[profit.verdict]}</span>
                            </span>
                        ) : (
                            <span className="text-muted-foreground">{t.report.noRange}</span>
                        )}
                    </li>
                );
            })}
        </ul>
    );
}
