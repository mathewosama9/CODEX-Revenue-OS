import { expect, reseed, signIn, test } from './fixtures';

test.describe('customer consent', () => {
    test.beforeAll(reseed);

    test('the owner uploads a consent CSV and sees what was added (review B1)', async ({ page, lang, t }) => {
        await signIn(page, lang, t, 'owner@demo.test');
        await page.getByRole('link', { name: t.nav.consent }).click();

        const form = page.getByTestId('consent-import');
        // 0551234567 already consented in the demo store; the other is new; "123" is invalid.
        await form.getByLabel(t.consent.file).setInputFiles({
            name: 'customers.csv',
            mimeType: 'text/csv',
            buffer: Buffer.from(`phone\n0551234567\n0552223333\n123\n`),
        });
        await expect(form.getByRole('button', { name: t.consent.submit })).toBeDisabled();
        await form.getByRole('checkbox').check();
        await form.getByRole('button', { name: t.consent.submit }).click();

        const result = page.getByTestId('consent-result');
        await expect(result.getByText(t.consent.result)).toBeVisible();
        await expect(result.locator('dd')).toHaveText(['1', '1', '0', '1']);
    });
});
