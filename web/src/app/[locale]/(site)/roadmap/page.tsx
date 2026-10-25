import { CtaBand } from '@/components/site/cta-band';
import { RoadmapTimeline } from '@/components/site/roadmap-timeline';
import { SiteSection } from '@/components/site/section';
import { getSite, siteMetadata } from '@/i18n/site/server';

export const generateMetadata = () => siteMetadata((s) => s.roadmap.meta);

export default async function RoadmapPage() {
    const { s } = await getSite();
    const copy = s.roadmap;

    return (
        <>
            <SiteSection>
                <header className="flex max-w-3xl flex-col gap-3">
                    <span className="text-sm font-medium text-link">{copy.hero.eyebrow}</span>
                    <h1 className="text-3xl font-semibold text-balance md:text-4xl">{copy.hero.title}</h1>
                    <p className="text-base text-pretty text-muted-foreground md:text-lg">{copy.hero.lead}</p>
                </header>
                <RoadmapTimeline />
                <p className="max-w-3xl text-lg font-medium text-balance">{copy.closing}</p>
            </SiteSection>
            <CtaBand title={copy.cta.title} body={copy.cta.body} />
        </>
    );
}
