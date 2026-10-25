'use client';

import { QueryClient, QueryClientProvider, useQueryClient } from '@tanstack/react-query';
import { usePathname, useRouter } from 'next/navigation';
import { Suspense, useEffect, useState } from 'react';
import type { ReactNode } from 'react';

import { I18nProvider } from '@/i18n';
import { ApiError, SESSION_EXPIRED_EVENT } from '@/lib/api';
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
            <QueryClientProvider client={client}>
                {/* Reads the URL, so it renders in the browser only. */}
                <Suspense>
                    <SessionWatcher locale={locale} />
                </Suspense>
                {children}
            </QueryClientProvider>
        </I18nProvider>
    );
}

const AUTH_PAGES = /^\/(ar|en)\/(login|register|forgot-password|reset-password|two-factor)/;

/**
 * The session can end mid-use (lifetime, sign-out in another tab). Any API
 * call that finds it gone sends the user to sign in, with a notice, instead
 * of a "try again" that can never succeed (review B8).
 */
function SessionWatcher({ locale }: { locale: Locale }) {
    const client = useQueryClient();
    const router = useRouter();
    const pathname = usePathname();

    useEffect(() => {
        const expired = () => {
            if (AUTH_PAGES.test(pathname)) {
                return;
            }

            client.clear();
            router.replace(`/${locale}/login?expired=1`);
        };

        window.addEventListener(SESSION_EXPIRED_EVENT, expired);

        return () => window.removeEventListener(SESSION_EXPIRED_EVENT, expired);
    }, [client, router, pathname, locale]);

    return null;
}
