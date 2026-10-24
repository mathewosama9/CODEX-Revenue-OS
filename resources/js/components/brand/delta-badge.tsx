import { formatPercent } from '@/lib/money';
import { cn } from '@/lib/utils';

type DeltaBadgeProps = {
    value: number;
    /** For metrics where down is good (cost, churn): flips the colour, never the arrow. */
    downIsGood?: boolean;
    className?: string;
};

// Arrow and sign are mandatory: colour never carries the meaning alone.
export function DeltaBadge({
    value,
    downIsGood = false,
    className,
}: DeltaBadgeProps) {
    const isFlat = value === 0;
    const isUp = value > 0;
    const isGood = downIsGood ? !isUp : isUp;

    const label = isFlat
        ? `● ${formatPercent(0)}`
        : `${isUp ? '▲ +' : '▼ −'}${formatPercent(value)}`;

    return (
        <span
            dir="ltr"
            className={cn(
                'inline-flex items-center rounded-sm border px-1.5 py-0.5 font-numeric text-[13px] font-medium tabular-nums',
                isFlat && 'border-muted-foreground text-muted-foreground',
                !isFlat && isGood && 'border-gain text-gain',
                !isFlat && !isGood && 'border-loss text-loss',
                className,
            )}
        >
            {label}
        </span>
    );
}
