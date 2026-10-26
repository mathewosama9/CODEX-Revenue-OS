import type { FeatureStatus } from '@/lib/features';
import { cn } from '@/lib/utils';

/** Always text, never colour alone: available (gain), beta (blue), coming soon (muted). */
export function StatusBadge({ status, label, className }: { status: FeatureStatus; label: string; className?: string }) {
    return (
        <span
            data-status={status}
            className={cn(
                'inline-flex w-fit items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
                status === 'live' && 'border-gain text-gain',
                status === 'beta' && 'border-link text-link',
                status === 'soon' && 'border-border-strong text-muted-foreground',
                className,
            )}
        >
            <span aria-hidden className={cn('size-1.5 rounded-full', status === 'live' && 'bg-gain', status === 'beta' && 'bg-link', status === 'soon' && 'bg-muted-foreground')} />
            {label}
        </span>
    );
}
