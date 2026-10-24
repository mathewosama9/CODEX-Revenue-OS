import { cn } from '@/lib/utils';

// The lockup is always LTR, even on Arabic pages (16-build-plan-and-brand §4.1).
export function Lockup({ className }: { className?: string }) {
    return (
        <span
            dir="ltr"
            className={cn(
                'inline-flex items-baseline gap-1.5 font-numeric tracking-tight',
                className,
            )}
        >
            <span className="text-lg font-semibold">CODEX</span>
            <span className="text-sm text-muted-foreground">Commerce</span>
        </span>
    );
}
