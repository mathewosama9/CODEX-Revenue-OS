import { Mail, MessageCircle } from 'lucide-react';

import { SiteSection } from '@/components/site/section';
import { Button } from '@/components/ui/button';
import { CONTACT } from '@/i18n/site';
import { getSite, siteMetadata } from '@/i18n/site/server';

export const generateMetadata = () => siteMetadata((s) => s.contact.meta);

const card = 'flex flex-col gap-4 rounded-xl rounded-ss-none border bg-card p-6 shadow-card';

// Until the founder sets NEXT_PUBLIC_CONTACT_*, say so instead of rendering a broken link.
export default async function ContactPage() {
    const { s } = await getSite();
    const copy = s.contact;
    const digits = CONTACT.whatsapp?.replace(/\D/g, '') || null;

    return (
        <SiteSection>
            <header className="flex max-w-3xl flex-col gap-3">
                <span className="text-sm font-medium text-link">{copy.hero.eyebrow}</span>
                <h1 className="text-3xl font-semibold text-balance md:text-4xl">{copy.hero.title}</h1>
                <p className="text-base text-pretty text-muted-foreground md:text-lg">{copy.hero.lead}</p>
            </header>

            <div className="grid gap-6 md:grid-cols-2">
                <div className={card} data-testid="contact-channels">
                    <h2 className="text-lg font-semibold">{copy.channels.title}</h2>
                    <div className="flex flex-col gap-3">
                        {digits ? (
                            <Button asChild size="lg" className="w-full sm:w-fit">
                                <a
                                    href={`https://wa.me/${digits}?text=${encodeURIComponent(copy.message)}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <MessageCircle aria-hidden />
                                    {copy.channels.whatsapp}
                                </a>
                            </Button>
                        ) : (
                            <p className="text-sm text-muted-foreground">{copy.channels.whatsappPending}</p>
                        )}
                        {CONTACT.email ? (
                            <div className="flex flex-col gap-1.5">
                                <Button asChild size="lg" variant="outline" className="w-full sm:w-fit">
                                    <a href={`mailto:${CONTACT.email}?subject=${encodeURIComponent(copy.emailSubject)}`}>
                                        <Mail aria-hidden />
                                        {copy.channels.email}
                                    </a>
                                </Button>
                                <span dir="ltr" className="text-sm break-all text-muted-foreground rtl:text-end">
                                    {CONTACT.email}
                                </span>
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">{copy.channels.emailPending}</p>
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground">{copy.channels.replyTime}</p>
                </div>

                <div className={card}>
                    <h2 className="text-lg font-semibold">{copy.needs.title}</h2>
                    <p className="text-pretty text-muted-foreground">{copy.needs.lead}</p>
                    <ol className="flex list-inside list-decimal flex-col gap-2 marker:font-numeric marker:text-muted-foreground">
                        {copy.needs.items.map((item) => (
                            <li key={item}>{item}</li>
                        ))}
                    </ol>
                </div>
            </div>
        </SiteSection>
    );
}
