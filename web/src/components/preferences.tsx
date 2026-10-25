'use client';

import { Languages, Moon, Sun } from 'lucide-react';
import Link from 'next/link';
import { usePathname, useSearchParams } from 'next/navigation';
import { Suspense, useSyncExternalStore } from 'react';

import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';

type Theme = 'navy' | 'light';

const THEME_EVENT = 'codex-theme';

// The <html> class is the source of truth (set before paint in the root layout).
// The server always renders Navy; the client value arrives after hydration.
function subscribe(callback: () => void) {
    window.addEventListener(THEME_EVENT, callback);

    return () => window.removeEventListener(THEME_EVENT, callback);
}

const readTheme = (): Theme =>
    document.documentElement.classList.contains('dark') ? 'navy' : 'light';

/** Language and theme switches. Both are browser preferences, not account data. */
export function Preferences() {
    const { t } = useI18n();
    const theme = useSyncExternalStore(subscribe, readTheme, (): Theme => 'navy');
    const toggleTheme = () => {
        const next = theme === 'navy' ? 'light' : 'navy';

        document.documentElement.classList.toggle('dark', next === 'navy');

        try {
            localStorage.setItem('theme', next);
        } catch {
            // Private mode: the switch still works for this page.
        }

        window.dispatchEvent(new Event(THEME_EVENT));
    };

    return (
        <div className="flex items-center gap-1">
            {/* The query string is read in the browser only; the fallback keeps the path. */}
            <Suspense fallback={<LocaleLink />}>
                <LocaleLinkWithQuery />
            </Suspense>
            <Button
                variant="ghost"
                size="icon"
                onClick={toggleTheme}
                aria-label={theme === 'navy' ? t.common.themeLight : t.common.themeNavy}
            >
                {theme === 'navy' ? <Sun /> : <Moon />}
            </Button>
        </div>
    );
}

function LocaleLinkWithQuery() {
    // Keep ?token=…&email=… (reset link) and other parameters (review H15).
    return <LocaleLink query={useSearchParams().toString()} />;
}

function LocaleLink({ query = '' }: { query?: string }) {
    const { locale, t } = useI18n();
    const pathname = usePathname();
    const other = locale === 'ar' ? 'en' : 'ar';

    return (
        <Button variant="ghost" size="sm" asChild>
            <Link
                href={`${pathname.replace(/^\/(ar|en)/, `/${other}`)}${query ? `?${query}` : ''}`}
                onClick={() => {
                    document.cookie = `locale=${other};path=/;max-age=31536000;samesite=lax`;
                }}
                data-testid="switch-locale"
            >
                <Languages />
                {t.common.otherLocale}
            </Link>
        </Button>
    );
}
