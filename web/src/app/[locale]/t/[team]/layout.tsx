import { Suspense } from 'react';

import { AppShell } from '@/components/app-shell';
import { Spinner } from '@/components/ui/spinner';

// The workspace comes from the URL and the data from the browser session, so
// the static shell is just a spinner; everything else streams in. Nothing here
// can be prerendered per merchant, so instant-navigation validation is off.
export const instant = false;

export default function TeamLayout({ children }: LayoutProps<'/[locale]/t/[team]'>) {
    return (
        <Suspense
            fallback={
                <div className="flex min-h-screen items-center justify-center">
                    <Spinner />
                </div>
            }
        >
            <AppShell>{children}</AppShell>
        </Suspense>
    );
}
