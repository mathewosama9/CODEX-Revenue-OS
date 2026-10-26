import { siteDictionary } from '../src/i18n/site';

import { expect, reseed, signIn, test } from './fixtures';

// Header links and their pages (src/components/site/site-header.tsx).
const HEADER_LINKS = [
    { key: 'howItWorks', path: '/how-it-works' },
    { key: 'features', path: '/features' },
    { key: 'aiAgent', path: '/ai-agent' },
    { key: 'pilot', path: '/pilot' },
    { key: 'security', path: '/security' },
] as const;

test.describe('public site', () => {
    test.beforeAll(reseed);

    test('the home page opens signed out, in the right direction', async ({ page, lang }) => {
        const s = siteDictionary(lang);

        await page.goto(`/${lang}`);
        await expect(page.locator('html')).toHaveAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
        await expect(page.getByRole('heading', { level: 1, name: s.home.hero.title })).toBeVisible();
        await expect(page.getByText(s.common.pilotBanner)).toBeVisible();
        await expect(page.getByText(s.common.example).first()).toBeVisible();
    });

    test('coming-soon features carry a text badge on the home page', async ({ page, lang }) => {
        const s = siteDictionary(lang);

        await page.goto(`/${lang}`);
        const soon = page.locator('[data-status="soon"]');
        await expect(soon.first()).toBeVisible();
        await expect(soon.first()).toHaveText(s.common.status.soon);
    });

    test('every header link opens its page', async ({ page, lang }) => {
        const s = siteDictionary(lang);
        const header = page.getByRole('banner');

        await page.goto(`/${lang}`);

        for (const link of HEADER_LINKS) {
            await header.getByRole('link', { name: s.common.nav[link.key], exact: true }).click();
            await expect(page).toHaveURL(new RegExp(`/${lang}${link.path}$`));
            await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
            await expect(header.getByRole('link', { name: s.common.nav[link.key], exact: true })).toHaveAttribute('aria-current', 'page');
        }
    });

    test('sign in goes to login, then through the workspaces to the team', async ({ page, lang, t }) => {
        const s = siteDictionary(lang);
        const visited: string[] = [];
        page.on('framenavigated', (frame) => {
            if (frame === page.mainFrame()) {
                visited.push(frame.url());
            }
        });

        await page.goto(`/${lang}`);
        await page.getByRole('banner').getByRole('link', { name: s.common.cta.signIn, exact: true }).click();
        await expect(page).toHaveURL(new RegExp(`/${lang}/login$`));

        await signIn(page, lang, t, 'owner@demo.test');
        await expect(page).toHaveURL(new RegExp(`/${lang}/t/[^/]+/`));
        expect(visited.some((url) => url.includes(`/${lang}/workspaces`))).toBe(true);
    });
});
