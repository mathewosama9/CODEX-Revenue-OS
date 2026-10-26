import Link from 'next/link';

import { Button } from '@/components/ui/button';
import { siteHref } from '@/i18n/site';
import { getSite } from '@/i18n/site/server';

/** The closing call to action of a site page: the pilot first, contact second. */
export async function CtaBand({ title, body }: { title: string; body?: string }) {
    const { locale, s } = await getSite();

    return (
        <section className="px-4 py-14 md:py-20">
            <div className="mx-auto flex w-full max-w-6xl flex-col items-start gap-5 rounded-2xl rounded-ss-none border bg-card p-8 shadow-card md:flex-row md:items-center md:justify-between md:p-10">
                <div className="flex max-w-2xl flex-col gap-2">
                    <h2 className="text-xl font-semibold text-balance md:text-2xl">{title}</h2>
                    {body && <p className="text-pretty text-muted-foreground">{body}</p>}
                </div>
                <div className="flex flex-wrap gap-3">
                    <Button asChild size="lg">
                        <Link href={siteHref(locale, '/pilot')}>{s.common.cta.requestPilot}</Link>
                    </Button>
                    <Button asChild size="lg" variant="outline">
                        <Link href={siteHref(locale, '/contact')}>{s.common.cta.contact}</Link>
                    </Button>
                </div>
            </div>
        </section>
    );
}
