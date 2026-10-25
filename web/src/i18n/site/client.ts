'use client';

import { useI18n } from '@/i18n';

import { siteDictionary } from '.';

/** For site client components (header menu, filters) and the in-app overview. */
export function useSite() {
    const { locale } = useI18n();

    return { locale, s: siteDictionary(locale) };
}
