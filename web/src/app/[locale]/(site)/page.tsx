import { ArrowRight, Check, MessagesSquare, ShieldCheck, TrendingUp, Wallet, X } from 'lucide-react';
import Link from 'next/link';

import { ComparisonTable } from '@/components/site/comparison-table';
import { CtaBand } from '@/components/site/cta-band';
import { Hero } from '@/components/site/hero';
import { ReportPreview } from '@/components/site/report-preview';
import { SiteSection } from '@/components/site/section';
import { StatusBadge } from '@/components/site/status-badge';
import { Button } from '@/components/ui/button';
import { siteHref } from '@/i18n/site';
import { getSite, siteMetadata } from '@/i18n/site/server';
import { COMING_NEXT, FEATURES } from '@/lib/features';

export const generateMetadata = () => siteMetadata((s) => s.home.meta);

const CARD = 'flex flex-col gap-3 rounded-xl rounded-ss-none border bg-card p-6 shadow-card';

const PILLARS = [
    { key: 'honestProfit', icon: TrendingUp },
    { key: 'lessSpend', icon: Wallet },
    { key: 'sellInChat', icon: MessagesSquare, status: 'beta' },
    { key: 'safe', icon: ShieldCheck },
] as const;

export default async function HomePage() {
    const { locale, s } = await getSite();
    const h = s.home;
    const steps = Object.values(h.steps.items);

    return (
        <>
            <Hero
                eyebrow={h.hero.eyebrow}
                title={h.hero.title}
                lead={h.hero.lead}
                actions={
                    <>
                        <Button asChild size="lg">
                            <Link href={siteHref(locale, '/pilot')}>{s.common.cta.requestPilot}</Link>
                        </Button>
                        <Button asChild size="lg" variant="outline">
                            <Link href={siteHref(locale, '/how-it-works')}>{s.common.cta.seeHow}</Link>
                        </Button>
                    </>
                }
                aside={<ReportPreview />}
            />

            <SiteSection muted eyebrow={h.problem.eyebrow} title={h.problem.title} lead={h.problem.body} />

            <SiteSection eyebrow={h.pillars.eyebrow} title={h.pillars.title}>
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {PILLARS.map((pillar) => {
                        const item = h.pillars.items[pillar.key];

                        return (
                            <li key={pillar.key} className={CARD}>
                                <span className="flex items-center justify-between gap-2">
                                    <pillar.icon aria-hidden className="size-6 text-link" strokeWidth={1.5} />
                                    {'status' in pillar && <StatusBadge status={pillar.status} label={s.common.status[pillar.status]} />}
                                </span>
                                <h3 className="text-lg font-semibold">{item.title}</h3>
                                <p className="text-sm text-pretty text-muted-foreground">{item.body}</p>
                            </li>
                        );
                    })}
                </ul>
            </SiteSection>

            <SiteSection muted eyebrow={h.steps.eyebrow} title={h.steps.title}>
                <ol className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {steps.map((step, index) => (
                        <li key={step.title} className={CARD}>
                            <span aria-hidden className="flex size-8 items-center justify-center rounded-full border border-link font-numeric text-sm font-semibold text-link">
                                {index + 1}
                            </span>
                            <h3 className="text-lg font-semibold">{step.title}</h3>
                            <p className="text-sm text-pretty text-muted-foreground">{step.body}</p>
                        </li>
                    ))}
                </ol>
                <Button asChild variant="link" className="w-fit px-0">
                    <Link href={siteHref(locale, '/how-it-works')}>
                        {h.steps.more}
                        <ArrowRight aria-hidden className="rtl:-scale-x-100" />
                    </Link>
                </Button>
            </SiteSection>

            <SiteSection eyebrow={h.comparison.eyebrow} title={h.comparison.title}>
                <ComparisonTable
                    caption={h.comparison.caption}
                    topic={h.comparison.topic}
                    usual={h.comparison.usual}
                    codex={h.comparison.codex}
                    rows={Object.values(h.comparison.rows)}
                />
            </SiteSection>

            <SiteSection muted eyebrow={h.fit.eyebrow} title={h.fit.title}>
                <div className="grid gap-4 md:grid-cols-2">
                    <div className={CARD}>
                        <h3 className="text-lg font-semibold">{h.fit.forTitle}</h3>
                        <ul className="flex flex-col gap-3 text-sm">
                            {Object.values(h.fit.for).map((line) => (
                                <li key={line} className="flex gap-2">
                                    <Check aria-hidden className="mt-0.5 size-4 shrink-0 text-gain" />
                                    {line}
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className={CARD}>
                        <h3 className="text-lg font-semibold">{h.fit.notTitle}</h3>
                        <ul className="flex flex-col gap-3 text-sm text-muted-foreground">
                            {Object.values(h.fit.not).map((line) => (
                                <li key={line} className="flex gap-2">
                                    <X aria-hidden className="mt-0.5 size-4 shrink-0 text-loss" />
                                    {line}
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </SiteSection>

            <SiteSection eyebrow={h.roadmap.eyebrow} title={h.roadmap.title} lead={h.roadmap.lead}>
                <ul className="grid gap-4 md:grid-cols-3">
                    {COMING_NEXT.map((id) => {
                        const feature = FEATURES.find((f) => f.id === id);
                        const item = s.features.items[id];

                        return (
                            <li key={id} className={CARD}>
                                {feature && <StatusBadge status={feature.status} label={s.common.status[feature.status]} />}
                                <h3 className="text-lg font-semibold">{item.title}</h3>
                                {item.body && <p className="text-sm text-pretty text-muted-foreground">{item.body}</p>}
                            </li>
                        );
                    })}
                </ul>
                <Button asChild variant="link" className="w-fit px-0">
                    <Link href={siteHref(locale, '/roadmap')}>
                        {s.common.cta.roadmap}
                        <ArrowRight aria-hidden className="rtl:-scale-x-100" />
                    </Link>
                </Button>
            </SiteSection>

            <CtaBand title={h.cta.title} body={h.cta.body} />
        </>
    );
}
