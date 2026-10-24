import type { ReactNode } from 'react';

import { DeltaBadge } from '@/components/brand/delta-badge';
import { cn } from '@/lib/utils';

type KpiCardProps = {
    /** "Arabic · English term", e.g. "ربح إضافي · Incremental profit". */
    label: string;
    value: string;
    unit?: string;
    delta?: number;
    comparisonLabel?: string;
    /** Up to 12 points, oldest first; the last one is the current period. */
    trend?: number[];
    /** Only the one money KPI of a screen sets this: gold value and last bar. */
    isMoney?: boolean;
    footer?: ReactNode;
};

export function KpiCard({
    label,
    value,
    unit,
    delta,
    comparisonLabel,
    trend = [],
    isMoney = false,
    footer,
}: KpiCardProps) {
    const max = Math.max(...trend, 1);

    return (
        <div className="flex flex-col gap-3 rounded-xl rounded-ss-none border bg-card p-6 shadow-card">
            <span className="font-numeric text-xs font-medium text-muted-foreground">
                {label}
            </span>

            <div className="flex items-baseline gap-2">
                <span
                    className={cn(
                        'font-numeric text-[32px] leading-tight font-semibold tabular-nums',
                        isMoney ? 'text-revenue-ink' : 'text-foreground',
                    )}
                >
                    {value}
                </span>
                {unit && (
                    <span className="text-[15px] text-muted-foreground">
                        {unit}
                    </span>
                )}
            </div>

            {delta !== undefined && (
                <div className="flex items-center gap-2">
                    <DeltaBadge value={delta} />
                    {comparisonLabel && (
                        <span className="text-[13px] text-muted-foreground">
                            {comparisonLabel}
                        </span>
                    )}
                </div>
            )}

            {trend.length > 0 && (
                <div dir="ltr" className="flex h-10 items-end gap-1">
                    {trend.slice(-12).map((point, index, points) => {
                        const isCurrent = index === points.length - 1;

                        return (
                            <span
                                key={index}
                                className={cn(
                                    'flex-1',
                                    !isCurrent && 'bg-chart-3',
                                    isCurrent &&
                                        (isMoney ? 'bg-chart-2' : 'bg-chart-1'),
                                )}
                                style={{ height: `${(point / max) * 100}%` }}
                            />
                        );
                    })}
                </div>
            )}

            {footer}
        </div>
    );
}
