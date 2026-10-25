import { RangeEstimate } from '@/components/brand/range-estimate';
import { formatSar } from '@/lib/money';
import { getSite } from '@/i18n/site/server';

// Sample figures in halalas, consistent with each other: incremental revenue × ~40% margin − cost ≈ profit.
// Never real data: the card always carries the "illustrative example" badge.
const SAMPLE = {
    profitLow: 120_000,
    profitHigh: 480_000,
    winProbability: 0.86,
    attributed: 1_840_000,
    incrementalLow: 650_000,
    incrementalHigh: 1_550_000,
    metaFees: 64_000,
    discounts: 76_000,
};

/** A sample weekly report. Its incremental profit is the page's one gold number. */
export async function ReportPreview() {
    const { locale, s } = await getSite();
    const r = s.home.report;
    const sar = (halalas: number) => formatSar(halalas, locale);

    const rows = [
        { label: r.attributed, value: sar(SAMPLE.attributed) },
        { label: r.incrementalRevenue, value: `${sar(SAMPLE.incrementalLow)} – ${sar(SAMPLE.incrementalHigh)}` },
        { label: r.metaFees, value: sar(SAMPLE.metaFees) },
        { label: r.discounts, value: sar(SAMPLE.discounts) },
    ];

    return (
        <div className="flex flex-col gap-4 rounded-2xl rounded-ss-none border bg-sunken p-4 shadow-card md:p-5" data-testid="report-preview">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="flex flex-col">
                    <span className="text-sm font-semibold">{r.title}</span>
                    <span className="text-xs text-muted-foreground">{r.period}</span>
                </span>
                <span className="rounded-full border border-border-strong px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                    {s.common.example}
                </span>
            </div>
            <RangeEstimate
                label={r.incrementalProfit}
                low={SAMPLE.profitLow}
                high={SAMPLE.profitHigh}
                winProbability={SAMPLE.winProbability}
                verdict="helpful"
                verdictLabel={r.verdict}
                probabilityLabel={r.probability}
                locale={locale}
            />
            <dl className="flex flex-col divide-y rounded-xl rounded-ss-none border bg-card px-4 text-sm">
                {rows.map((row) => (
                    <div key={row.label} className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-3">
                        <dt className="text-muted-foreground">{row.label}</dt>
                        <dd dir="ltr" className="font-numeric tabular-nums">{row.value}</dd>
                    </div>
                ))}
            </dl>
            <p className="text-xs text-muted-foreground">{r.note}</p>
        </div>
    );
}
