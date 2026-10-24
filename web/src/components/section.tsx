import type { ReactNode } from 'react';

/** A brand card (Cypher corner) with a heading row. */
export function Section({
    title,
    subtitle,
    actions,
    children,
    testId,
}: {
    title: string;
    subtitle?: string;
    actions?: ReactNode;
    children: ReactNode;
    testId?: string;
}) {
    return (
        <section
            data-testid={testId}
            className="flex flex-col gap-4 rounded-2xl rounded-ss-none border bg-card p-6 shadow-card"
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="flex flex-col gap-1">
                    <h2 className="text-base font-semibold">{title}</h2>
                    {subtitle && <p className="text-sm text-muted-foreground">{subtitle}</p>}
                </div>
                {actions && <div className="flex items-center gap-2">{actions}</div>}
            </div>
            {children}
        </section>
    );
}
