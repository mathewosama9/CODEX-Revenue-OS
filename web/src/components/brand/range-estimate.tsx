import { formatSar } from '@/lib/money';
import type { Locale } from '@/lib/money';
import { cn } from '@/lib/utils';

export type EstimateVerdict = 'helpful' | 'harmful' | 'inconclusive';

type RangeEstimateProps = {
    label: string;
    /** Lower and upper bound of the interval, in halalas. */
    low: number;
    high: number;
    /** Probability (0–1) that the true effect is positive. */
    winProbability: number;
    verdict: EstimateVerdict;
    /** Shown when the sample is small, e.g. "نحتاج حوالي 2,400 عميل إضافي". */
    explanation?: string;
    /** From the dictionary, so the copy stays in one place (review L5). */
    verdictLabel: string;
    probabilityLabel: string;
    locale?: Locale;
    /** Only the one money figure of a screen is gold; others stay ink when helpful. */
    isMoney?: boolean;
};

// An incremental figure is never shown without its interval (US-E07-01).
// Gold only when the estimate is conclusively positive; inconclusive stays muted.
export function RangeEstimate({
    label,
    low,
    high,
    winProbability,
    verdict,
    explanation,
    verdictLabel,
    probabilityLabel,
    locale = 'ar',
    isMoney = true,
}: RangeEstimateProps) {
    const isHelpful = verdict === 'helpful';

    return (
        <div className="flex flex-col gap-3 rounded-xl rounded-ss-none border bg-card p-6 shadow-card">
            <span className="font-numeric text-xs font-medium text-muted-foreground">
                {label}
            </span>

            <div
                dir="ltr"
                className={cn(
                    'font-numeric text-[32px] leading-tight font-semibold tabular-nums',
                    isHelpful && (isMoney ? 'text-revenue-ink' : 'text-foreground'),
                    verdict === 'harmful' && 'text-loss',
                    verdict === 'inconclusive' && 'text-muted-foreground',
                )}
            >
                {formatSar(low, locale)} – {formatSar(high, locale)}
            </div>

            <div className="flex flex-wrap items-center gap-2 text-[13px]">
                <span
                    className={cn(
                        'rounded-full border px-2 py-0.5 font-medium',
                        isHelpful && 'border-gain text-gain',
                        verdict === 'harmful' && 'border-loss text-loss',
                        verdict === 'inconclusive' &&
                            'border-muted-foreground text-muted-foreground',
                    )}
                >
                    {verdictLabel}
                </span>
                <span className="text-muted-foreground">
                    {probabilityLabel}{' '}
                    <span dir="ltr" className="font-numeric tabular-nums">
                        {Math.round(winProbability * 100)}%
                    </span>
                </span>
            </div>

            {explanation && (
                <p className="text-[13px] text-muted-foreground">
                    {explanation}
                </p>
            )}
        </div>
    );
}
