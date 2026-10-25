import { getSite } from '@/i18n/site/server';
import { FEATURES, HORIZONS } from '@/lib/features';
import { cn } from '@/lib/utils';

import { StatusBadge } from './status-badge';

/** The roadmap by horizon: a vertical line on phones, four columns on desktop. Every feature, grouped or not. */
export async function RoadmapTimeline() {
    const { s } = await getSite();

    return (
        <ol className="grid gap-8 border-s border-border-strong ps-6 lg:grid-cols-4 lg:gap-4 lg:border-s-0 lg:ps-0">
            {HORIZONS.map((horizon, index) => {
                const items = FEATURES.filter((f) => f.horizon === horizon);

                return (
                    <li
                        key={horizon}
                        aria-labelledby={`horizon-${horizon}`}
                        className="relative flex flex-col gap-4 animate-in fade-in slide-in-from-bottom-2 duration-500 motion-reduce:animate-none lg:border-t lg:border-border-strong lg:pt-6"
                    >
                        <span
                            aria-hidden
                            className={cn(
                                'absolute -start-[31px] top-1 size-3.5 rounded-full border-2 border-background lg:-top-[7px] lg:start-0',
                                index === 0 ? 'bg-gain' : 'bg-border-strong',
                            )}
                        />
                        <header className="flex flex-col gap-1">
                            <span className="font-numeric text-xs text-muted-foreground">{index + 1}</span>
                            <h2 id={`horizon-${horizon}`} className="text-lg font-semibold">
                                {s.common.horizon[horizon]}
                            </h2>
                            <p className="text-sm text-pretty text-muted-foreground">{s.roadmap.horizons[horizon]}</p>
                        </header>
                        <ul className="flex flex-col gap-2">
                            {items.map((f) => (
                                <li key={f.id} className="flex flex-col gap-2 rounded-xl rounded-ss-none border bg-card p-4 shadow-card">
                                    <StatusBadge status={f.status} label={s.common.status[f.status]} />
                                    <span className="text-sm font-medium text-pretty">{s.features.items[f.id].title}</span>
                                </li>
                            ))}
                        </ul>
                    </li>
                );
            })}
        </ol>
    );
}
