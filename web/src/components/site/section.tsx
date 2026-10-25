import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/** A site page band: 16px gutters on phones, a 6xl column on desktop. */
export function SiteSection({
    id,
    eyebrow,
    title,
    lead,
    children,
    className,
    muted = false,
}: {
    id?: string;
    eyebrow?: string;
    title?: string;
    lead?: string;
    children?: ReactNode;
    className?: string;
    /** A sunken background, to separate bands. */
    muted?: boolean;
}) {
    return (
        <section id={id} className={cn('px-4 py-14 md:py-20', muted && 'bg-sunken', className)}>
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-8">
                {(eyebrow || title || lead) && (
                    <header className="flex max-w-3xl flex-col gap-3">
                        {eyebrow && <span className="text-sm font-medium text-link">{eyebrow}</span>}
                        {title && <h2 className="text-2xl font-semibold text-balance md:text-3xl">{title}</h2>}
                        {lead && <p className="text-base text-pretty text-muted-foreground md:text-lg">{lead}</p>}
                    </header>
                )}
                {children}
            </div>
        </section>
    );
}
