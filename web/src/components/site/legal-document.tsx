import { TriangleAlert } from 'lucide-react';
import Link from 'next/link';
import type { Metadata } from 'next';

import { SiteSection } from '@/components/site/section';
import { siteHref } from '@/i18n/site';
import type { SiteDictionary } from '@/i18n/site';
import { getSite, siteMetadata } from '@/i18n/site/server';

type Doc = 'privacy' | 'terms';

/** Drafts are never indexed, even after launch, until the lawyer signs them off. */
export async function legalMetadata(doc: Doc): Promise<Metadata> {
    return {
        ...(await siteMetadata((s: SiteDictionary) => s.legal[doc].meta)),
        robots: { index: false, follow: false },
    };
}

/** A skeleton of what the final document will cover, under a prominent draft banner. */
export async function LegalDocument({ doc }: { doc: Doc }) {
    const { locale, s } = await getSite();
    const copy = s.legal[doc];

    return (
        <SiteSection>
            <div
                role="note"
                data-testid="legal-draft"
                className="flex items-start gap-3 rounded-xl rounded-ss-none border-2 border-link bg-card p-5 shadow-card"
            >
                <TriangleAlert aria-hidden className="mt-0.5 size-5 shrink-0 text-link" />
                <div className="flex flex-col gap-1">
                    <strong className="font-semibold">{s.legal.draft.title}</strong>
                    <p className="text-sm text-pretty text-muted-foreground">{s.legal.draft.body}</p>
                </div>
            </div>

            <header className="flex max-w-3xl flex-col gap-3">
                <h1 className="text-3xl font-semibold text-balance md:text-4xl">{copy.title}</h1>
                <p className="text-base text-pretty text-muted-foreground md:text-lg">{copy.lead}</p>
                <span className="text-sm text-muted-foreground">{s.legal.updated}</span>
            </header>

            <ol className="flex max-w-3xl flex-col gap-6">
                {copy.sections.map((section, index) => (
                    <li key={section.title} className="flex flex-col gap-1.5">
                        <h2 className="text-lg font-semibold">
                            <span className="font-numeric text-muted-foreground">{index + 1}.</span> {section.title}
                        </h2>
                        <p className="text-pretty text-muted-foreground">{section.body}</p>
                    </li>
                ))}
            </ol>

            <p className="text-sm text-muted-foreground">
                <Link href={siteHref(locale, '/contact')} className="text-link hover:underline">
                    {s.legal.questions}
                </Link>
            </p>
        </SiteSection>
    );
}
