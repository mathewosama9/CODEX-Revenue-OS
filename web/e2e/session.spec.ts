import { expect, reseed, signIn, test } from './fixtures';

test.describe('session and outages', () => {
    test.beforeAll(reseed);

    test('an expired session sends the user to sign in, with a notice (B8)', async ({ page, lang, t }) => {
        await signIn(page, lang, t, 'owner@demo.test');
        await expect(page).toHaveURL(/overview$/);
        // Let the overview's requests finish first: a late response's Set-Cookie would restore the session.
        await page.waitForLoadState('networkidle');

        // The session ends server-side (lifetime, sign-out elsewhere).
        await page.context().clearCookies();
        // A screen the overview has not just loaded (its cards' data is still fresh in the cache).
        await page.getByRole('link', { name: t.nav.knowledge }).click();

        await expect(page).toHaveURL(new RegExp(`/${lang}/login\\?expired=1$`));
        await expect(page.getByText(t.auth.sessionExpired)).toBeVisible();
    });

    test('an unreachable API shows an error with retry, not an endless spinner (H8)', async ({ page, lang, t }) => {
        await signIn(page, lang, t, 'owner@demo.test');
        await expect(page).toHaveURL(/overview$/);

        await page.route('**/api/v1/me', (route) => route.abort('connectionrefused'));
        await page.reload();

        await expect(page.getByText(t.common.apiDown)).toBeVisible();
        await page.unroute('**/api/v1/me');
        await page.getByRole('button', { name: t.common.retry }).click();
        await expect(page.getByRole('heading', { name: t.overview.title })).toBeVisible();
    });
});
