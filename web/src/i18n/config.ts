import type { Locale } from '@/lib/money';

export const LOCALES = ['ar', 'en'] as const satisfies readonly Locale[];

export function isLocale(value: string): value is Locale {
    return (LOCALES as readonly string[]).includes(value);
}

export const dir = (locale: Locale) => (locale === 'ar' ? 'rtl' : 'ltr');
