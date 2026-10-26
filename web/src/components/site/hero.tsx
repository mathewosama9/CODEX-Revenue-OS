import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/** The top band of a site page: the page's h1, a lead, actions, and an optional aside (report preview). */
export function Hero({
    eyebrow,
    title,
    lead,
    actions,
    aside,
}: {
    eyebrow?: string;
    title: string;
    lead?: string;
    actions?: ReactNode;
    aside?: ReactNode;
}) {
    return (
        <section className="px-4 pt-12 pb-14 md:pt-20 md:pb-20">
            <div className={cn('mx-auto grid w-full max-w-6xl items-center gap-10', aside && 'lg:grid-cols-[1.15fr_1fr]')}>
                <div className="flex max-w-3xl animate-in flex-col gap-5 duration-700 fade-in slide-in-from-bottom-2 motion-reduce:animate-none">
                    {eyebrow && <span className="text-sm font-medium text-link">{eyebrow}</span>}
                    <h1 className="text-3xl leading-tight font-semibold text-balance md:text-5xl md:leading-tight">{title}</h1>
                    {lead && <p className="text-base text-pretty text-muted-foreground md:text-lg">{lead}</p>}
                    {actions && <div className="flex flex-wrap gap-3 pt-2">{actions}</div>}
                </div>
                {aside && (
                    <div className="animate-in delay-150 duration-700 fill-mode-both fade-in slide-in-from-bottom-4 motion-reduce:animate-none">
                        {aside}
                    </div>
                )}
            </div>
        </section>
    );
}
