'use client';

import { Languages, Moon, Sun } from 'lucide-react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';

function readTheme(): 'navy' | 'light' {
    return typeof document !== 'undefined' &&
        !document.documentElement.classList.contains('dark')
        ? 'light'
        : 'navy';
}

/** Language and theme switches. Both are browser preferences, not account data. */
export function Preferences() {
    const { locale, t } = useI18n();
    const pathname = usePathname();
    const [theme, setTheme] = useState(readTheme);
    const other = locale === 'ar' ? 'en' : 'ar';

    const toggleTheme = () => {
        const next = theme === 'navy' ? 'light' : 'navy';

        document.documentElement.classList.toggle('dark', next === 'navy');

        try {
            localStorage.setItem('theme', next);
        } catch {
            // Private mode: the switch still works for this page.
        }

        setTheme(next);
    };

    return (
        <div className="flex items-center gap-1">
            <Button variant="ghost" size="sm" asChild>
                <Link
                    href={pathname.replace(/^\/(ar|en)/, `/${other}`)}
                    onClick={() => {
                        document.cookie = `locale=${other};path=/;max-age=31536000;samesite=lax`;
                    }}
                    data-testid="switch-locale"
                >
                    <Languages />
                    {t.common.otherLocale}
                </Link>
            </Button>
            <Button
                variant="ghost"
                size="icon"
                onClick={toggleTheme}
                aria-label={theme === 'navy' ? t.common.themeLight : t.common.themeNavy}
                suppressHydrationWarning
            >
                {theme === 'navy' ? <Sun /> : <Moon />}
            </Button>
        </div>
    );
}
