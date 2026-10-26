'use client';

import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { ChevronLeft } from 'lucide-react';
import Link from 'next/link';
import type { ReactNode } from 'react';
import { useState } from 'react';

import { RangeEstimate } from '@/components/brand/range-estimate';
import { checklistPasses } from '@/components/go-live-checklist';
import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { StatusBadge } from '@/components/site/status-badge';
import { format, useI18n } from '@/i18n';
import { useSite } from '@/i18n/site/client';
import { api, getData } from '@/lib/api';
import { COMING_NEXT, FEATURES } from '@/lib/features';
import { useHref, useTeam } from '@/lib/session';
import { shiftDate, workspaceToday } from '@/lib/time';
import type { AgentSettings, Approval, ConversationSummary, ProfitReport, Recovery, ReplyDraft, Usage } from '@/lib/types';
import { cn } from '@/lib/utils';

// Same keys and defaults as the screens the cards link to, so their caches are shared:
// the report's 28 days on the workspace clock (report/screen.tsx), the usage screen's month.
function defaultPeriod(timeZone: string) {
    const to = workspaceToday(timeZone);

    return { from: shiftDate(to, -27), to };
}

const thisMonth = (timeZone: string) => workspaceToday(timeZone).slice(0, 7);
const count = new Intl.NumberFormat('en-US');
const usd = (value: number) => `$${value.toFixed(2)}`;

export default function OverviewPage() {
    const { t } = useI18n();
    const { path, slug, can, timezone } = useTeam();
    const [period] = useState(() => defaultPeriod(timezone));
    const [month] = useState(() => thisMonth(timezone));

    // Only what this member may see is fetched; the API refuses the rest anyway.
    const report = useQuery({
        queryKey: [slug, 'report', period],
        queryFn: () => getData<ProfitReport>(path(`/reports/profit?from=${period.from}&to=${period.to}`)),
        enabled: can('reports:view'),
    });

    const recovery = useQuery({
        queryKey: [slug, 'recovery'],
        queryFn: () => getData<Recovery>(path('/recovery')),
        enabled: can('recovery:manage'),
    });

    const approvals = useQuery({
        queryKey: [slug, 'approvals'],
        queryFn: () => getData<Approval[]>(path('/recovery/approvals')),
        enabled: can('discounts:manage'),
    });

    const drafts = useQuery({
        queryKey: [slug, 'ai-drafts', 'pending'],
        queryFn: () => getData<ReplyDraft[]>(path('/ai/drafts?status=pending')),
        enabled: can('ai-drafts:review'),
    });

    // The inbox screen's infinite query; only its first page is loaded here.
    const inbox = useInfiniteQuery({
        queryKey: [slug, 'inbox', 'open'],
        queryFn: ({ pageParam }) =>
            api<{ data: ConversationSummary[]; meta: { next_cursor: string | null } }>(
                'GET',
                `/api/v1${path(`/inbox?status=open${pageParam ? `&cursor=${pageParam}` : ''}`)}`,
            ),
        initialPageParam: '',
        getNextPageParam: (page) => page.meta.next_cursor ?? undefined,
        select: (data) => data.pages.flatMap((page) => page.data),
        enabled: can('inbox:use'),
    });

    const ai = useQuery({
        queryKey: [slug, 'ai-settings'],
        queryFn: () => getData<AgentSettings>(path('/ai/settings')),
        enabled: can('ai:autonomy'),
    });

    const usage = useQuery({
        queryKey: [slug, 'usage', month],
        queryFn: () => getData<Usage>(path(`/billing/usage?month=${month}`)),
        enabled: can('billing:manage'),
    });

    const setupIncomplete =
        can('integrations:manage') && recovery.data !== undefined && !checklistPasses(recovery.data.go_live_checklist);

    return (
        <>
            <PageHeader title={t.overview.title} subtitle={t.overview.subtitle} />

            <div className="grid gap-4 md:grid-cols-2" data-testid="overview">
                {setupIncomplete && recovery.data && <SetupCard recovery={recovery.data} />}

                {can('reports:view') && (
                    <div className="md:col-span-2" data-testid="overview-profit">
                        <QueryState query={report}>{(data) => <ProfitCard report={data} />}</QueryState>
                    </div>
                )}

                {can('recovery:manage') && (
                    <OverviewCard title={t.overview.recovery.title} to="/recovery" action={t.overview.recovery.action} testId="overview-recovery">
                        <QueryState query={recovery}>
                            {(data) => (
                                <span
                                    data-enabled={data.settings.enabled}
                                    className={cn(
                                        'w-fit rounded-full border px-3 py-1 text-sm font-medium',
                                        data.settings.enabled
                                            ? 'border-gain text-gain'
                                            : 'border-muted-foreground text-muted-foreground',
                                    )}
                                >
                                    {data.settings.enabled ? `● ${t.recovery.statusOn}` : `○ ${t.recovery.statusOff}`}
                                </span>
                            )}
                        </QueryState>
                    </OverviewCard>
                )}

                {can('discounts:manage') && (
                    <OverviewCard title={t.overview.approvals.title} to="/approvals" testId="overview-approvals">
                        <QueryState query={approvals}>
                            {(items) => <Count n={items.length} label={t.overview.approvals.waiting} none={t.overview.approvals.none} />}
                        </QueryState>
                    </OverviewCard>
                )}

                {can('ai-drafts:review') && (
                    <OverviewCard title={t.overview.drafts.title} to="/drafts" testId="overview-drafts">
                        <QueryState query={drafts}>
                            {(items) => <Count n={items.length} label={t.overview.drafts.waiting} none={t.overview.drafts.none} />}
                        </QueryState>
                    </OverviewCard>
                )}

                {can('inbox:use') && (
                    <OverviewCard title={t.overview.inbox.title} to="/inbox" testId="overview-inbox">
                        <QueryState query={inbox}>
                            {(items) => (
                                <Count
                                    n={inbox.hasNextPage ? `${count.format(items.length)}+` : items.length}
                                    label={t.overview.inbox.open}
                                    none={t.overview.inbox.none}
                                />
                            )}
                        </QueryState>
                    </OverviewCard>
                )}

                {can('ai:autonomy') && (
                    <OverviewCard title={t.overview.ai.title} to="/ai" testId="overview-ai">
                        <QueryState query={ai}>
                            {(data) => (
                                <div className="flex flex-col gap-1">
                                    <span className="font-medium">{t.ai.settings.modes[data.mode] ?? data.mode}</span>
                                    <span className="text-sm text-muted-foreground">
                                        {format(t.overview.ai.spent, {
                                            spent: usd(data.spent_today_usd),
                                            cap: usd(data.daily_cap_usd),
                                        })}
                                    </span>
                                </div>
                            )}
                        </QueryState>
                    </OverviewCard>
                )}

                {can('billing:manage') && (
                    <OverviewCard title={t.overview.usage.title} to="/usage" testId="overview-usage">
                        <QueryState query={usage}>{(data) => <UsageSummary usage={data.usage} />}</QueryState>
                    </OverviewCard>
                )}

                <ComingSoonCard />
            </div>
        </>
    );
}

/** A brand card (one square corner) with a title, its content and a link to its screen. */
function OverviewCard({
    title,
    to,
    action,
    testId,
    children,
    className,
}: {
    title: string;
    /** Path inside the workspace, e.g. /inbox. */
    to: string;
    action?: string;
    testId: string;
    children: ReactNode;
    className?: string;
}) {
    const { t } = useI18n();
    const href = useHref();
    const { slug } = useTeam();

    return (
        <section
            data-testid={testId}
            className={cn('flex flex-col gap-4 rounded-xl rounded-ss-none border bg-card p-6 shadow-card', className)}
        >
            <h2 className="text-base font-semibold">{title}</h2>
            <div className="flex-1">{children}</div>
            <MoreLink href={href(`/t/${slug}${to}`)} label={action ?? t.overview.open} />
        </section>
    );
}

function MoreLink({ href, label }: { href: string; label: string }) {
    return (
        <Link href={href} className="flex w-fit items-center gap-1 text-sm text-link hover:underline">
            {label}
            <ChevronLeft aria-hidden className="size-4 ltr:-scale-x-100" />
        </Link>
    );
}

function Count({ n, label, none }: { n: number | string; label: string; none: string }) {
    if (n === 0) {
        return <p className="text-sm text-muted-foreground">{none}</p>;
    }

    return <p className="text-lg font-medium">{format(label, { n })}</p>;
}

function SetupCard({ recovery }: { recovery: Recovery }) {
    const { t } = useI18n();
    const steps = Object.values(recovery.go_live_checklist);

    return (
        <OverviewCard
            title={t.overview.setup.title}
            to="/onboarding"
            action={t.overview.setup.action}
            testId="overview-setup"
            className="border-primary md:col-span-2"
        >
            <div className="flex flex-col gap-1">
                <p className="text-lg font-medium">
                    {format(t.overview.setup.progress, { done: steps.filter(Boolean).length, total: steps.length })}
                </p>
                <p className="text-sm text-muted-foreground">{t.overview.setup.body}</p>
            </div>
        </OverviewCard>
    );
}

/** The screen's only gold figure, and only when the estimate is conclusively positive (RangeEstimate). */
function ProfitCard({ report }: { report: ProfitReport }) {
    const { t, locale } = useI18n();
    const href = useHref();
    const { slug, currency } = useTeam();
    const link = <MoreLink href={href(`/t/${slug}/report`)} label={t.overview.profit.action} />;

    if (report.status === 'frozen') {
        return (
            <div className="flex flex-col gap-3 rounded-xl rounded-ss-none border bg-card p-6 shadow-card">
                <span className="text-xs font-medium text-muted-foreground">{t.report.incrementalProfit}</span>
                <p className="font-medium" data-testid="overview-profit-frozen">
                    {t.report.frozenTitle}
                </p>
                <p className="text-sm text-muted-foreground">{t.report.frozenBody}</p>
                {link}
            </div>
        );
    }

    const profit = report.incremental_profit_halalas;

    if (profit.low === null || profit.high === null || profit.win_probability === null) {
        return (
            <div className="flex flex-col gap-3 rounded-xl rounded-ss-none border bg-card p-6 shadow-card">
                <span className="text-xs font-medium text-muted-foreground">
                    {t.report.incrementalProfit} · {t.overview.profit.period}
                </span>
                <p className="font-medium" data-testid="overview-profit-collecting">
                    {t.overview.profit.collecting}
                </p>
                <p className="text-sm text-muted-foreground">{t.report.noRange}</p>
                {link}
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-3">
            <RangeEstimate
                label={`${t.report.incrementalProfit} · ${t.overview.profit.period}`}
                low={profit.low}
                high={profit.high}
                currency={currency}
                winProbability={profit.win_probability}
                verdict={profit.verdict}
                verdictLabel={t.report.verdictLabel[profit.verdict]}
                probabilityLabel={t.report.winProbability}
                explanation={
                    report.verdict === 'inconclusive' && report.additional_customers_needed > 0
                        ? format(t.report.needMore, { n: report.additional_customers_needed })
                        : undefined
                }
                locale={locale}
                isMoney
            />
            {link}
        </div>
    );
}

function UsageSummary({ usage }: { usage: Usage['usage'] }) {
    const { t } = useI18n();
    // "messages_delivered:marketing" and friends add up to one figure here.
    const messages = Object.entries(usage)
        .filter(([metric]) => metric.split(':')[0] === 'messages_delivered')
        .reduce((sum, [, total]) => sum + total, 0);
    const rows = [
        [t.overview.usage.messages, messages],
        [t.overview.usage.carts, usage.carts_abandoned ?? 0],
    ] as const;

    return (
        <dl className="flex flex-col gap-1 text-sm">
            {rows.map(([label, value]) => (
                <div key={label} className="flex justify-between gap-4">
                    <dt className="text-muted-foreground">{label}</dt>
                    <dd className="font-numeric tabular-nums">{count.format(value)}</dd>
                </div>
            ))}
        </dl>
    );
}

function ComingSoonCard() {
    const { t } = useI18n();
    const { s } = useSite();
    const href = useHref();

    return (
        <section
            data-testid="overview-soon"
            className="flex flex-col gap-4 rounded-xl rounded-ss-none border bg-card p-6 shadow-card md:col-span-2"
        >
            <div className="flex flex-col gap-1">
                <h2 className="text-base font-semibold">{t.overview.soon.title}</h2>
                <p className="text-sm text-muted-foreground">{t.overview.soon.body}</p>
            </div>
            <ul className="flex flex-col gap-2">
                {COMING_NEXT.map((id) => {
                    const status = FEATURES.find((feature) => feature.id === id)?.status ?? 'soon';

                    return (
                        <li key={id} className="flex flex-wrap items-center justify-between gap-2">
                            <span>{s.features.items[id].title}</span>
                            <StatusBadge status={status} label={s.common.status[status]} />
                        </li>
                    );
                })}
            </ul>
            <MoreLink href={href('/roadmap')} label={t.overview.soon.action} />
        </section>
    );
}
