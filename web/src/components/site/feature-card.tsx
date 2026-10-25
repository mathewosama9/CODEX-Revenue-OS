import type { FeatureStatus } from '@/lib/features';
import { cn } from '@/lib/utils';

import { StatusBadge } from './status-badge';

/** One feature: its status first (always as text), then what it does. */
export function FeatureCard({
    title,
    body,
    status,
    statusLabel,
    className,
}: {
    title: string;
    body: string;
    status: FeatureStatus;
    statusLabel: string;
    className?: string;
}) {
    return (
        <article
            data-status={status}
            className={cn(
                'flex flex-col gap-3 rounded-xl rounded-ss-none border bg-card p-5 shadow-card',
                'animate-in fade-in slide-in-from-bottom-2 duration-300 motion-reduce:animate-none',
                className,
            )}
        >
            <StatusBadge status={status} label={statusLabel} />
            <h3 className="font-semibold text-balance">{title}</h3>
            <p className="text-sm text-pretty text-muted-foreground">{body}</p>
        </article>
    );
}
