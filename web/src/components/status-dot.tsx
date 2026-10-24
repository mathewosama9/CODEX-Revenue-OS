import { CircleCheck, CircleDashed } from 'lucide-react';

import { cn } from '@/lib/utils';

/** Done / not done, with an icon so colour never carries the meaning alone. */
export function StatusLine({ ok, label }: { ok: boolean; label: string }) {
    return (
        <span
            data-ok={ok}
            className={cn('flex items-center gap-2 text-sm', ok ? 'text-gain' : 'text-muted-foreground')}
        >
            {ok ? (
                <CircleCheck className="size-4 shrink-0" strokeWidth={1.5} />
            ) : (
                <CircleDashed className="size-4 shrink-0" strokeWidth={1.5} />
            )}
            <span className={ok ? 'text-foreground' : undefined}>{label}</span>
        </span>
    );
}
