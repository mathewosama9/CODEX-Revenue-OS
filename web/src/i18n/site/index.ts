// The public site's copy (docs/website/site-plan.md). One file per page under
// ar/ and en/, so pages can change independently; English must match Arabic's keys.
// Plain data: server pages read it with getSite(), client parts with useSite().
import type { Locale } from '@/lib/money';

import { aiAgent as arAiAgent } from './ar/aiAgent';
import { common as arCommon } from './ar/common';
import { contact as arContact } from './ar/contact';
import { faq as arFaq } from './ar/faq';
import { features as arFeatures } from './ar/features';
import { home as arHome } from './ar/home';
import { howItWorks as arHowItWorks } from './ar/howItWorks';
import { legal as arLegal } from './ar/legal';
import { pilot as arPilot } from './ar/pilot';
import { roadmap as arRoadmap } from './ar/roadmap';
import { security as arSecurity } from './ar/security';
import { aiAgent as enAiAgent } from './en/aiAgent';
import { common as enCommon } from './en/common';
import { contact as enContact } from './en/contact';
import { faq as enFaq } from './en/faq';
import { features as enFeatures } from './en/features';
import { home as enHome } from './en/home';
import { howItWorks as enHowItWorks } from './en/howItWorks';
import { legal as enLegal } from './en/legal';
import { pilot as enPilot } from './en/pilot';
import { roadmap as enRoadmap } from './en/roadmap';
import { security as enSecurity } from './en/security';
import type { Widen } from './widen';

const siteAr = {
    common: arCommon,
    home: arHome,
    howItWorks: arHowItWorks,
    features: arFeatures,
    aiAgent: arAiAgent,
    pilot: arPilot,
    security: arSecurity,
    roadmap: arRoadmap,
    faq: arFaq,
    contact: arContact,
    legal: arLegal,
};

export type SiteDictionary = Widen<typeof siteAr>;

const siteEn: SiteDictionary = {
    common: enCommon,
    home: enHome,
    howItWorks: enHowItWorks,
    features: enFeatures,
    aiAgent: enAiAgent,
    pilot: enPilot,
    security: enSecurity,
    roadmap: enRoadmap,
    faq: enFaq,
    contact: enContact,
    legal: enLegal,
};

export function siteDictionary(locale: Locale): SiteDictionary {
    return locale === 'ar' ? siteAr : siteEn;
}

/** Site paths, e.g. siteHref('ar', '/pilot') → /ar/pilot; '' is the home page. */
export const siteHref = (locale: Locale, path: string) => `/${locale}${path}`;

/** Fills {name} placeholders; usable in server components (the app's format() is client-only). */
export function fill(template: string, values: Record<string, string | number>): string {
    return template.replace(/\{(\w+)\}/g, (match, key: string) => (key in values ? String(values[key]) : match));
}

/** Contact links come from the environment; null until the founder sets them. */
export const CONTACT = {
    whatsapp: process.env.NEXT_PUBLIC_CONTACT_WHATSAPP || null,
    email: process.env.NEXT_PUBLIC_CONTACT_EMAIL || null,
};

/** The site stays out of search engines until launch (after Gate 0). */
export const SITE_INDEXABLE = process.env.NEXT_PUBLIC_SITE_INDEXABLE === 'true';
