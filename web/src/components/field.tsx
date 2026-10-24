import type { ComponentProps } from 'react';

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function Field({
    label,
    help,
    error,
    id,
    ...props
}: ComponentProps<typeof Input> & {
    id: string;
    label: string;
    help?: string;
    error?: string;
}) {
    return (
        <div className="flex flex-col gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Input id={id} aria-invalid={error ? true : undefined} {...props} />
            {help && !error && (
                <p className="text-xs text-muted-foreground">{help}</p>
            )}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}
