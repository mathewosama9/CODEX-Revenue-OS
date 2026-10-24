'use client';

import { createContext, useContext } from 'react';
import type { ReactNode } from 'react';

import type { Locale } from '@/lib/money';

import { ar } from './ar';
import type { Dictionary } from './ar';
import { en } from './en';

const dictionaries: Record<Locale, Dictionary> = { ar, en };

const I18nContext = createContext<{ locale: Locale; t: Dictionary }>({
    locale: 'ar',
    t: ar,
});

export function I18nProvider({
    locale,
    children,
}: {
    locale: Locale;
    children: ReactNode;
}) {
    return (
        <I18nContext value={{ locale, t: dictionaries[locale] }}>
            {children}
        </I18nContext>
    );
}

export function useI18n() {
    return useContext(I18nContext);
}

/** Fills {name} placeholders. Numbers use Latin digits in both languages. */
export function format(
    template: string,
    values: Record<string, string | number>,
): string {
    return template.replace(/\{(\w+)\}/g, (_, key: string) =>
        String(
            typeof values[key] === 'number'
                ? new Intl.NumberFormat('en-US').format(values[key])
                : (values[key] ?? ''),
        ),
    );
}
