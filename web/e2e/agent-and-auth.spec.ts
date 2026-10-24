import { expect, reseed, signIn, test } from './fixtures';

test.describe('agent and sign-in', () => {
    test.beforeAll(reseed);

    test('wrong password stays on the login page with an error', async ({ page, lang, t }) => {
        await page.goto(`/${lang}/login`);
        await page.getByLabel(t.auth.email, { exact: true }).fill('owner@demo.test');
        await page.getByLabel(t.auth.password, { exact: true }).fill('wrong-password');
        await page.getByRole('button', { name: t.auth.login }).click();
        await expect(page.locator('#email')).toHaveAttribute('aria-invalid', 'true');
        await expect(page).toHaveURL(new RegExp(`/${lang}/login$`));
    });

    test('the app redirects to login when signed out', async ({ page, lang }) => {
        await page.goto(`/${lang}/t/anything/report`);
        await expect(page).toHaveURL(new RegExp(`/${lang}/login$`));
    });

    test('an agent only sees the inbox', async ({ page, lang, t }) => {
        await signIn(page, lang, t, 'agent@demo.test');
        await expect(page).toHaveURL(new RegExp(`/${lang}/t/[^/]+/inbox$`));

        const nav = page.getByRole('navigation');
        await expect(nav.getByRole('link')).toHaveCount(1);
        await expect(nav.getByRole('link', { name: t.nav.inbox })).toBeVisible();

        // Typing an owner URL directly: the API refuses, the page says so.
        const team = page.url().split('/t/')[1].split('/')[0];
        await page.goto(`/${lang}/t/${team}/report`);
        await expect(page.getByText(t.common.forbidden).first()).toBeVisible();
    });

    test('switching language keeps the page and flips direction', async ({ page, lang, t }) => {
        await page.goto(`/${lang}/login`);
        await page.getByTestId('switch-locale').click();
        const other = lang === 'ar' ? 'en' : 'ar';
        await expect(page).toHaveURL(new RegExp(`/${other}/login$`));
        await expect(page.locator('html')).toHaveAttribute('dir', other === 'ar' ? 'rtl' : 'ltr');
        await expect(page.locator('html')).toHaveAttribute('lang', other);
        void t;
    });
});
