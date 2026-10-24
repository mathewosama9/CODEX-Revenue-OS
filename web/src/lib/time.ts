import type { Locale } from '@/lib/money';

// Latin digits in both languages (brand rule); Riyadh is the merchant's clock.
export function formatDateTime(iso: string, locale: Locale): string {
    return new Intl.DateTimeFormat(locale === 'ar' ? 'ar-SA-u-nu-latn-ca-gregory' : 'en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Asia/Riyadh',
    }).format(new Date(iso));
}

export function formatDate(iso: string, locale: Locale): string {
    return new Intl.DateTimeFormat(locale === 'ar' ? 'ar-SA-u-nu-latn-ca-gregory' : 'en-GB', {
        dateStyle: 'medium',
        timeZone: 'Asia/Riyadh',
    }).format(new Date(iso));
}

export function formatRelative(iso: string, locale: Locale): string {
    const minutes = Math.round((new Date(iso).getTime() - Date.now()) / 60_000);
    const format = new Intl.RelativeTimeFormat(locale === 'ar' ? 'ar-u-nu-latn' : 'en', { numeric: 'auto' });

    return Math.abs(minutes) < 60
        ? format.format(minutes, 'minute')
        : Math.abs(minutes) < 48 * 60
          ? format.format(Math.round(minutes / 60), 'hour')
          : format.format(Math.round(minutes / 1440), 'day');
}
