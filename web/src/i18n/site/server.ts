import { locale as localeParam } from 'next/root-params';
import type { Metadata } from 'next';

import { isLocale } from '@/i18n/config';
import type { Locale } from '@/lib/money';

import { siteDictionary } from '.';
import type { SiteDictionary } from '.';

/** For site server components: the locale from the URL and its copy. */
export async function getSite(): Promise<{ locale: Locale; s: SiteDictionary }> {
    const value = await localeParam();
    const locale: Locale = isLocale(value) ? value : 'ar';

    return { locale, s: siteDictionary(locale) };
}

/** Page metadata: "<page title> · CODEX Commerce" and the page description. */
export async function siteMetadata(pick: (s: SiteDictionary) => { title: string; description: string }): Promise<Metadata> {
    const { s } = await getSite();
    const meta = pick(s);

    return {
        title: meta.title ? `${meta.title} · ${s.common.brandSuffix}` : s.common.brandSuffix,
        description: meta.description || undefined,
    };
}
