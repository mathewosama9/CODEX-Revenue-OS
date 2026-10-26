import type { Locale } from '@/lib/money';

// Latin digits in both languages (brand rule). Times read on the merchant's clock:
// the workspace timezone from /me (useTeam().timezone, ADR-012), Riyadh by default.
const DEFAULT_TIMEZONE = 'Asia/Riyadh';

export function formatDateTime(iso: string, locale: Locale, timeZone: string = DEFAULT_TIMEZONE): string {
    return new Intl.DateTimeFormat(locale === 'ar' ? 'ar-SA-u-nu-latn-ca-gregory' : 'en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone,
    }).format(new Date(iso));
}

export function formatDate(iso: string, locale: Locale, timeZone: string = DEFAULT_TIMEZONE): string {
    // A bare calendar day ("2026-10-01") parses as UTC midnight: read it in UTC so no zone shifts it a day.
    const dayOnly = /^\d{4}-\d{2}-\d{2}$/.test(iso);

    return new Intl.DateTimeFormat(locale === 'ar' ? 'ar-SA-u-nu-latn-ca-gregory' : 'en-GB', {
        dateStyle: 'medium',
        timeZone: dayOnly ? 'UTC' : timeZone,
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

/** Today's date in the workspace timezone as YYYY-MM-DD: the merchant's calendar, not UTC (review H6, L3). */
export function workspaceToday(timeZone: string = DEFAULT_TIMEZONE): string {
    return new Intl.DateTimeFormat('en-CA', { timeZone }).format(new Date());
}

/** YYYY-MM-DD shifted by whole days (calendar arithmetic, no time zone involved). */
export function shiftDate(date: string, days: number): string {
    const [year, month, day] = date.split('-').map(Number);

    return new Date(Date.UTC(year, month - 1, day + days)).toISOString().slice(0, 10);
}
