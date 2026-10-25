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
            {/* Screen readers announce the help or the error with the field (review L6). */}
            <Input
                id={id}
                aria-invalid={error ? true : undefined}
                aria-describedby={error || help ? `${id}-note` : undefined}
                {...props}
            />
            {help && !error && (
                <p id={`${id}-note`} className="text-xs text-muted-foreground">
                    {help}
                </p>
            )}
            {error && (
                <p id={`${id}-note`} className="text-xs text-destructive" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}
