'use client';

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useState } from 'react';
import type { ReactNode } from 'react';

import { I18nProvider } from '@/i18n';
import { ApiError } from '@/lib/api';
import type { Locale } from '@/lib/money';

export function Providers({
    locale,
    children,
}: {
    locale: Locale;
    children: ReactNode;
}) {
    const [client] = useState(
        () =>
            new QueryClient({
                defaultOptions: {
                    queries: {
                        staleTime: 30_000,
                        // Auth and permission errors won't fix themselves.
                        retry: (count, error) =>
                            !(error instanceof ApiError && error.status < 500) &&
                            count < 2,
                    },
                },
            }),
    );

    return (
        <I18nProvider locale={locale}>
            <QueryClientProvider client={client}>{children}</QueryClientProvider>
        </I18nProvider>
    );
}
