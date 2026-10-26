// Money from the API is integer minor units of the workspace currency (ADR-012):
// amount × 10^exponent (2 for SAR/EGP, 3 for KWD, 0 for IQD). Figures inside the
// product always use Latin digits; Arabic puts the symbol after the amount,
// English puts the code before it.
import type { WorkspaceCurrency } from '@/lib/session';

export type { WorkspaceCurrency } from '@/lib/session';

export type Locale = 'ar' | 'en';

/** The Saudi riyal: the marketing site and the Pilot price stay in riyals. */
export const SAR: WorkspaceCurrency = {
    code: 'SAR',
    exponent: 2,
    symbol_ar: 'ر.س',
    symbol_en: 'SAR',
};

const groupFormatter = new Intl.NumberFormat('en-US', {
    maximumFractionDigits: 0,
});

export function currencySymbol(currency: WorkspaceCurrency, locale: Locale): string {
    return locale === 'ar' ? currency.symbol_ar : currency.symbol_en;
}

type FormatOptions = {
    /** Whole units only (estimates and ranges), rounded half away from zero. */
    whole?: boolean;
};

/**
 * 34500 EGP → "345 ج.م" / "EGP 345"; 34550 → "345.50 ج.م"; 12345 KWD →
 * "12.345 د.ك" (3-decimal currencies always show their fils); 25000 IQD →
 * "25,000 د.ع". Integer math only, so the last digit is exact.
 */
export function formatMoney(
    minor: number,
    currency: WorkspaceCurrency,
    locale: Locale = 'ar',
    { whole = false }: FormatOptions = {},
): string {
    const scale = 10 ** currency.exponent;
    const negative = minor < 0;
    let units = Math.trunc(Math.abs(minor) / scale);
    let fraction = Math.abs(minor) % scale;

    if (whole && currency.exponent > 0) {
        units += fraction * 2 >= scale ? 1 : 0;
        fraction = 0;
    }

    const showFraction =
        !whole && currency.exponent > 0 && (currency.exponent === 3 || fraction !== 0);
    let amount = groupFormatter.format(units);

    if (showFraction) {
        amount += `.${String(fraction).padStart(currency.exponent, '0')}`;
    }

    // Never "-0 ر.س" for a range bound under one unit (review L7).
    if (negative && (units !== 0 || (showFraction && fraction !== 0))) {
        amount = `-${amount}`;
    }

    return locale === 'ar'
        ? `${amount} ${currency.symbol_ar}`
        : `${currency.symbol_en} ${amount}`;
}

/** Riyals in whole units: the marketing site only (Pilot price, examples). */
export function formatSar(halalas: number, locale: Locale = 'ar'): string {
    return formatMoney(halalas, SAR, locale, { whole: true });
}

/** A major-unit input ("2500.50") for this currency → minor units, or null if invalid. */
export function toMinor(input: string, currency: WorkspaceCurrency): number | null {
    const pattern =
        currency.exponent === 0
            ? /^\d+$/
            : new RegExp(`^\\d+(?:\\.\\d{1,${currency.exponent}})?$`);
    const value = input.trim();

    if (!pattern.test(value)) {
        return null;
    }

    const [units, fraction = ''] = value.split('.');

    return Number(units) * 10 ** currency.exponent + Number(fraction.padEnd(currency.exponent, '0') || '0');
}

/** Minor units → the major-unit string an input shows ("2500.5" → "2500.50"). */
export function toMajorInput(minor: number, currency: WorkspaceCurrency): string {
    if (currency.exponent === 0) {
        return String(minor);
    }

    const scale = 10 ** currency.exponent;
    const fraction = minor % scale;

    return fraction === 0
        ? String(Math.trunc(minor / scale))
        : `${Math.trunc(minor / scale)}.${String(fraction).padStart(currency.exponent, '0')}`;
}

export function formatPercent(value: number, fractionDigits = 1): string {
    return `${Math.abs(value).toFixed(fractionDigits)}%`;
}
