// Money is stored as integer halalas (docs/blueprint/09-technical-architecture.md §9.5).
// Figures inside the product always use Latin digits; the currency follows the number.
const amountFormatter = new Intl.NumberFormat('en-US', {
    maximumFractionDigits: 0,
});

export const CURRENCY_LABEL = { ar: 'ر.س', en: 'SAR' } as const;

export type Locale = keyof typeof CURRENCY_LABEL;

export function formatSar(halalas: number, locale: Locale = 'ar'): string {
    // `|| 0` turns -0 (a range bound under 1 riyal) into 0, never "-0 ر.س" (review L7).
    const amount = amountFormatter.format(Math.round(halalas / 100) || 0);

    return locale === 'ar'
        ? `${amount} ${CURRENCY_LABEL.ar}`
        : `${CURRENCY_LABEL.en} ${amount}`;
}

export function formatPercent(value: number, fractionDigits = 1): string {
    return `${Math.abs(value).toFixed(fractionDigits)}%`;
}
