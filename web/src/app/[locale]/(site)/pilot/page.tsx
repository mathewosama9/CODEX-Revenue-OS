import { Check, ShieldCheck } from 'lucide-react';
import Link from 'next/link';

import { SiteSection } from '@/components/site/section';
import { Button } from '@/components/ui/button';
import { siteHref } from '@/i18n/site';
import { getSite, siteMetadata } from '@/i18n/site/server';

export const generateMetadata = () => siteMetadata((s) => s.pilot.meta);

const card = 'flex flex-col gap-4 rounded-xl rounded-ss-none border bg-card p-6 shadow-card';

function CheckList({ items }: { items: readonly string[] }) {
    return (
        <ul className="flex flex-col gap-3">
            {items.map((item) => (
                <li key={item} className="flex items-start gap-3">
                    <Check aria-hidden className="mt-1 size-4 shrink-0 text-gain" />
                    <span className="text-pretty">{item}</span>
                </li>
            ))}
        </ul>
    );
}

// Payment collection waits for the lawyer (docs/phases/P0/proof-pilot-offer.md),
// so every call to action here goes to the contact page, never to a checkout.
export default async function PilotPage() {
    const { locale, s } = await getSite();
    const copy = s.pilot;
    const contact = siteHref(locale, '/contact');

    return (
        <>
            <SiteSection>
                <header className="flex max-w-3xl flex-col gap-3">
                    <span className="text-sm font-medium text-link">{copy.hero.eyebrow}</span>
                    <h1 className="text-3xl font-semibold text-balance md:text-4xl">{copy.hero.title}</h1>
                    <p className="text-base text-pretty text-muted-foreground md:text-lg">{copy.hero.lead}</p>
                </header>

                <div className="grid gap-6 md:grid-cols-5">
                    {/* The price is plain ink: gold is for the merchant's money, not ours. */}
                    <div className={`${card} md:col-span-2`} data-testid="pilot-price">
                        <span className="text-sm font-medium text-muted-foreground">{copy.price.label}</span>
                        <div className="flex flex-col gap-1">
                            <span className="font-numeric text-4xl font-semibold tabular-nums">{copy.price.amount}</span>
                            <span className="text-lg font-medium">{copy.price.period}</span>
                        </div>
                        <div className="flex flex-col gap-1 text-sm text-muted-foreground">
                            <span>{copy.price.upfront}</span>
                            <span>{copy.price.vat}</span>
                        </div>
                        <Button asChild size="lg" className="w-full sm:w-fit">
                            <Link href={contact}>{copy.price.cta}</Link>
                        </Button>
                        <p className="text-[13px] text-muted-foreground">{copy.price.ctaNote}</p>
                    </div>

                    <div className={`${card} md:col-span-3`}>
                        <h2 className="text-lg font-semibold">{copy.included.title}</h2>
                        <CheckList items={copy.included.items} />
                    </div>
                </div>
            </SiteSection>

            <SiteSection muted>
                <div className="grid gap-6 md:grid-cols-2">
                    <div className={card}>
                        <h2 className="text-lg font-semibold">{copy.meta_fees.title}</h2>
                        <p className="text-pretty text-muted-foreground">{copy.meta_fees.body}</p>
                    </div>
                    <div className={card}>
                        <h2 className="text-lg font-semibold">{copy.honesty.title}</h2>
                        <p className="text-pretty text-muted-foreground">{copy.honesty.body}</p>
                    </div>
                </div>
            </SiteSection>

            <SiteSection>
                <div className="grid gap-6 md:grid-cols-2">
                    <div className={card} data-testid="pilot-guarantee">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <ShieldCheck aria-hidden className="size-5 text-gain" />
                            {copy.guarantee.title}
                        </h2>
                        <CheckList items={copy.guarantee.items} />
                    </div>
                    <div className={card}>
                        <h2 className="text-lg font-semibold">{copy.conditions.title}</h2>
                        <p className="text-pretty text-muted-foreground">{copy.conditions.lead}</p>
                        <CheckList items={copy.conditions.items} />
                    </div>
                </div>

                <div className={card}>
                    <h2 className="text-lg font-semibold">{copy.after.title}</h2>
                    <p className="text-pretty text-muted-foreground">{copy.after.body}</p>
                    <Button asChild variant="outline" className="w-fit">
                        <Link href={contact}>{copy.after.cta}</Link>
                    </Button>
                </div>
            </SiteSection>

            <section className="px-4 py-14 md:py-20">
                <div className="mx-auto flex w-full max-w-6xl flex-col items-start gap-5 rounded-2xl rounded-ss-none border bg-card p-8 shadow-card md:flex-row md:items-center md:justify-between md:p-10">
                    <div className="flex max-w-2xl flex-col gap-2">
                        <h2 className="text-xl font-semibold text-balance md:text-2xl">{copy.cta.title}</h2>
                        <p className="text-pretty text-muted-foreground">{copy.cta.body}</p>
                    </div>
                    <Button asChild size="lg">
                        <Link href={contact}>{copy.cta.button}</Link>
                    </Button>
                </div>
            </section>
        </>
    );
}
