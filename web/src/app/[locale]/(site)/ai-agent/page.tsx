import { Ban, Check, Gauge, ShieldCheck, UserRound, UsersRound } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { ChatExample } from '@/components/site/chat-example';
import { CtaBand } from '@/components/site/cta-band';
import { SiteSection } from '@/components/site/section';
import { StatusBadge } from '@/components/site/status-badge';
import { getSite, siteMetadata } from '@/i18n/site/server';

export const generateMetadata = () => siteMetadata((s) => s.aiAgent.meta);

const CARD = 'flex flex-col gap-3 rounded-xl rounded-ss-none border bg-card p-6 shadow-card';

function List({ items, icon: Icon, iconClass }: { items: string[]; icon: LucideIcon; iconClass: string }) {
    return (
        <ul className="flex flex-col gap-2.5">
            {items.map((item) => (
                <li key={item} className="flex items-start gap-2.5 text-sm text-pretty">
                    <Icon aria-hidden className={`mt-0.5 size-4 shrink-0 ${iconClass}`} />
                    <span>{item}</span>
                </li>
            ))}
        </ul>
    );
}

function InfoCard({ icon: Icon, title, body }: { icon: LucideIcon; title: string; body: string }) {
    return (
        <article className={CARD}>
            <Icon aria-hidden className="size-5 text-link" />
            <h3 className="font-semibold">{title}</h3>
            <p className="text-sm text-pretty text-muted-foreground">{body}</p>
        </article>
    );
}

export default async function AiAgentPage() {
    const { s } = await getSite();
    const copy = s.aiAgent;

    return (
        <>
            <SiteSection>
                <div className="grid items-center gap-10 lg:grid-cols-[1fr_auto]">
                    <header className="flex max-w-2xl flex-col gap-4 animate-in fade-in slide-in-from-bottom-2 duration-500 motion-reduce:animate-none">
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="text-sm font-medium text-link">{copy.hero.eyebrow}</span>
                            <StatusBadge status="beta" label={s.common.status.beta} />
                        </div>
                        <h1 className="text-3xl font-semibold text-balance md:text-4xl">{copy.hero.title}</h1>
                        <p className="text-base text-pretty text-muted-foreground md:text-lg">{copy.hero.lead}</p>
                    </header>
                    <ChatExample className="justify-self-center lg:justify-self-end" />
                </div>
            </SiteSection>

            <SiteSection muted>
                <div className="grid gap-4 md:grid-cols-2">
                    <article className={CARD}>
                        <h2 className="text-lg font-semibold">{copy.can.title}</h2>
                        <List items={copy.can.items} icon={Check} iconClass="text-gain" />
                    </article>
                    <article className={CARD}>
                        <h2 className="text-lg font-semibold">{copy.cannot.title}</h2>
                        <p className="text-sm text-muted-foreground">{copy.cannot.lead}</p>
                        <List items={copy.cannot.items} icon={Ban} iconClass="text-muted-foreground" />
                    </article>
                </div>
            </SiteSection>

            <SiteSection>
                <div className="grid gap-4 md:grid-cols-2">
                    <InfoCard icon={ShieldCheck} title={copy.numbers.title} body={copy.numbers.body} />
                    <article className={CARD}>
                        <UserRound aria-hidden className="size-5 text-link" />
                        <h3 className="font-semibold">{copy.handoff.title}</h3>
                        <List items={copy.handoff.items} icon={Check} iconClass="text-link" />
                    </article>
                    <InfoCard icon={UsersRound} title={copy.privacy.title} body={copy.privacy.body} />
                    <InfoCard icon={Gauge} title={copy.cap.title} body={copy.cap.body} />
                </div>
            </SiteSection>

            <SiteSection muted>
                <div className="grid gap-4 md:grid-cols-2">
                    <article className={CARD}>
                        <StatusBadge status="beta" label={s.common.status.beta} />
                        <h2 className="text-lg font-semibold">{copy.assisted.title}</h2>
                        <p className="text-sm text-pretty text-muted-foreground">{copy.assisted.body}</p>
                    </article>
                    <article className={CARD}>
                        <StatusBadge status="soon" label={s.common.status.soon} />
                        <h2 className="text-lg font-semibold">{copy.assisted.autoTitle}</h2>
                        <p className="text-sm text-pretty text-muted-foreground">{copy.assisted.autoBody}</p>
                    </article>
                </div>
            </SiteSection>

            <CtaBand title={copy.cta.title} body={copy.cta.body} />
        </>
    );
}
