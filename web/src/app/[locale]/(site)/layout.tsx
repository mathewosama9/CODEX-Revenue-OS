import type { Metadata } from 'next';

import { SiteFooter } from '@/components/site/site-footer';
import { SiteHeader } from '@/components/site/site-header';
import { SITE_INDEXABLE } from '@/i18n/site';

// The app stays noindex (root layout); the public site only once launched (Gate 0).
export const metadata: Metadata = {
    robots: SITE_INDEXABLE ? { index: true, follow: true } : { index: false, follow: false },
};

export default function SiteLayout({ children }: LayoutProps<'/[locale]'>) {
    return (
        <div className="flex min-h-screen flex-col">
            <SiteHeader />
            <main className="flex-1">{children}</main>
            <SiteFooter />
        </div>
    );
}
