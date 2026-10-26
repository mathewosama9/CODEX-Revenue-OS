'use client';

import { useState } from 'react';

import { useSite } from '@/i18n/site/client';
import type { FeatureStatus } from '@/lib/features';
import { FEATURE_GROUPS, FEATURES } from '@/lib/features';
import { cn } from '@/lib/utils';

import { FeatureCard } from './feature-card';

type Filter = 'all' | 'live' | 'soon';

const FILTERS: Filter[] = ['all', 'live', 'soon'];

/** "Available" covers beta too: it is in the product, in approval mode. */
function matches(filter: Filter, status: FeatureStatus): boolean {
    if (filter === 'all') {
        return true;
    }

    return filter === 'soon' ? status === 'soon' : status !== 'soon';
}

/** The features page grid, grouped, with a status filter. Groups left empty by the filter are hidden. */
export function FeatureGrid() {
    const { s } = useSite();
    const [filter, setFilter] = useState<Filter>('all');
    const copy = s.features;

    return (
        <div className="flex flex-col gap-10">
            <div role="group" aria-labelledby="feature-filter" className="flex flex-wrap items-center gap-2">
                <span id="feature-filter" className="text-sm text-muted-foreground">
                    {copy.filter.label}
                </span>
                {FILTERS.map((value) => (
                    <button
                        key={value}
                        type="button"
                        aria-pressed={filter === value}
                        onClick={() => setFilter(value)}
                        className={cn(
                            'rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors',
                            'focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                            filter === value ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:bg-accent',
                        )}
                    >
                        {copy.filter[value]}
                    </button>
                ))}
            </div>

            {FEATURE_GROUPS.map((group) => {
                const items = FEATURES.filter((f) => f.group === group && matches(filter, f.status));

                if (items.length === 0) {
                    return null;
                }

                return (
                    <section key={group} aria-labelledby={`group-${group}`} className="flex flex-col gap-4">
                        <header className="flex flex-col gap-1">
                            <h2 id={`group-${group}`} className="text-xl font-semibold">
                                {s.common.group[group]}
                            </h2>
                            <p className="text-sm text-pretty text-muted-foreground">{copy.groups[group]}</p>
                        </header>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {items.map((f) => (
                                <FeatureCard
                                    // Re-mount on filter change so the fade-in plays again.
                                    key={`${f.id}-${filter}`}
                                    title={copy.items[f.id].title}
                                    body={copy.items[f.id].body}
                                    status={f.status}
                                    statusLabel={s.common.status[f.status]}
                                />
                            ))}
                        </div>
                    </section>
                );
            })}
        </div>
    );
}
