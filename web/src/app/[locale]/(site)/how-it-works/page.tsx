import { Check, MessageCircle, ShoppingCart } from 'lucide-react';

import { CtaBand } from '@/components/site/cta-band';
import { Hero } from '@/components/site/hero';
import { HoldoutDiagram } from '@/components/site/holdout-diagram';
import { ReportPreview } from '@/components/site/report-preview';
import { SiteSection } from '@/components/site/section';
import { getSite, siteMetadata } from '@/i18n/site/server';
import { cn } from '@/lib/utils';

export const generateMetadata = () => siteMetadata((s) => s.howItWorks.meta);

const CARD = 'flex flex-col gap-3 rounded-xl rounded-ss-none border bg-card p-6 shadow-card';

export default async function HowItWorksPage() {
    const { s } = await getSite();
    const h = s.howItWorks;
    const verdicts = [
        { label: h.report.verdicts.helpful, tone: 'border-gain text-gain' },
        { label: h.report.verdicts.harmful, tone: 'border-loss text-loss' },
        { label: h.report.verdicts.inconclusive, tone: 'border-muted-foreground text-muted-foreground' },
    ];

    return (
        <>
            <Hero eyebrow={h.hero.eyebrow} title={h.hero.title} lead={h.hero.lead} />

            <SiteSection muted eyebrow={h.connect.eyebrow} title={h.connect.title}>
                <div className="grid gap-4 md:grid-cols-2">
                    {[
                        { ...h.connect.salla, icon: ShoppingCart },
                        { ...h.connect.whatsapp, icon: MessageCircle },
                    ].map((item) => (
                        <div key={item.title} className={CARD}>
                            <item.icon aria-hidden className="size-6 text-link" strokeWidth={1.5} />
                            <h3 className="text-lg font-semibold">{item.title}</h3>
                            <p className="text-sm text-pretty text-muted-foreground">{item.body}</p>
                        </div>
                    ))}
                </div>
            </SiteSection>

            <SiteSection eyebrow={h.eligibility.eyebrow} title={h.eligibility.title} lead={h.eligibility.lead}>
                <ul className="flex max-w-3xl flex-col gap-3">
                    {Object.values(h.eligibility.items).map((line) => (
                        <li key={line} className="flex gap-3 rounded-xl rounded-ss-none border bg-card p-4">
                            <Check aria-hidden className="mt-0.5 size-4 shrink-0 text-gain" />
                            {line}
                        </li>
                    ))}
                </ul>
            </SiteSection>

            <SiteSection muted eyebrow={h.holdout.eyebrow} title={h.holdout.title}>
                <div className="grid items-center gap-8 md:grid-cols-2">
                    <p className="text-base text-pretty md:text-lg">{h.holdout.body}</p>
                    <HoldoutDiagram label={h.holdout.diagramLabel} treated={h.holdout.treated} control={h.holdout.control} />
                </div>
            </SiteSection>

            <SiteSection eyebrow={h.discount.eyebrow} title={h.discount.title} lead={h.discount.body} />

            <SiteSection muted eyebrow={h.report.eyebrow} title={h.report.title}>
                <div className="grid items-start gap-8 lg:grid-cols-2">
                    <div className="flex flex-col gap-5">
                        <p className="text-muted-foreground">{h.report.lead}</p>
                        <ol className="flex flex-col gap-3">
                            {Object.values(h.report.items).map((line, index) => (
                                <li key={line} className="flex gap-3">
                                    <span aria-hidden className="flex size-6 shrink-0 items-center justify-center rounded-full border border-link font-numeric text-xs font-semibold text-link">
                                        {index + 1}
                                    </span>
                                    {line}
                                </li>
                            ))}
                        </ol>
                        <p className="text-muted-foreground">{h.report.verdictsTitle}</p>
                        <ul className="flex flex-wrap gap-2">
                            {verdicts.map((verdict) => (
                                <li key={verdict.label} className={cn('rounded-full border px-3 py-1 text-sm font-medium', verdict.tone)}>
                                    {verdict.label}
                                </li>
                            ))}
                        </ul>
                    </div>
                    <ReportPreview />
                </div>
            </SiteSection>

            <SiteSection eyebrow={h.honesty.eyebrow} title={h.honesty.title} lead={h.honesty.body} />

            <CtaBand title={h.cta.title} body={h.cta.body} />
        </>
    );
}
