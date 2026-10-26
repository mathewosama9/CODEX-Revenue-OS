import { Info, ShieldCheck } from 'lucide-react';

import { CtaBand } from '@/components/site/cta-band';
import { SiteSection } from '@/components/site/section';
import { StatusBadge } from '@/components/site/status-badge';
import { getSite, siteMetadata } from '@/i18n/site/server';

export const generateMetadata = () => siteMetadata((s) => s.security.meta);

const CARD = 'flex flex-col gap-2 rounded-xl rounded-ss-none border bg-card p-5 shadow-card';

export default async function SecurityPage() {
    const { s } = await getSite();
    const copy = s.security;

    return (
        <>
            <SiteSection>
                <header className="flex max-w-3xl flex-col gap-3 animate-in fade-in slide-in-from-bottom-2 duration-500 motion-reduce:animate-none">
                    <span className="text-sm font-medium text-link">{copy.hero.eyebrow}</span>
                    <h1 className="text-3xl font-semibold text-balance md:text-4xl">{copy.hero.title}</h1>
                    <p className="text-base text-pretty text-muted-foreground md:text-lg">{copy.hero.lead}</p>
                </header>

                {/* LEGAL REVIEW: the wording of this note must be approved by the lawyer before publishing. */}
                <aside role="note" className="flex items-start gap-3 rounded-xl rounded-ss-none border border-border-strong bg-sunken p-5">
                    <Info aria-hidden className="mt-0.5 size-5 shrink-0 text-link" />
                    <p className="text-sm text-pretty">
                        <span className="font-semibold">{copy.providers.title}: </span>
                        {copy.providers.body}
                    </p>
                </aside>
            </SiteSection>

            <SiteSection muted>
                <div className="flex flex-wrap items-center gap-3">
                    <h2 className="text-2xl font-semibold">{copy.available.title}</h2>
                    <StatusBadge status="live" label={s.common.status.live} />
                </div>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {copy.available.items.map((item) => (
                        <article key={item.title} className={CARD}>
                            <ShieldCheck aria-hidden className="size-5 text-gain" />
                            <h3 className="font-semibold">{item.title}</h3>
                            <p className="text-sm text-pretty text-muted-foreground">{item.body}</p>
                        </article>
                    ))}
                </div>
            </SiteSection>

            <SiteSection>
                <h2 className="text-2xl font-semibold">{copy.planned.title}</h2>
                <div className="grid gap-4 sm:grid-cols-2">
                    {copy.planned.items.map((item) => (
                        <article key={item.title} className={CARD}>
                            <StatusBadge status="soon" label={s.common.status.soon} />
                            <h3 className="font-semibold">{item.title}</h3>
                            <p className="text-sm text-pretty text-muted-foreground">{item.body}</p>
                        </article>
                    ))}
                </div>
            </SiteSection>

            <CtaBand title={copy.cta.title} body={copy.cta.body} />
        </>
    );
}
