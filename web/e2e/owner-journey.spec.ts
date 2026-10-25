import { expect, reseed, signIn, test } from './fixtures';

// The P1 journey a merchant does with our support:
// login → onboarding → enable → report → approval → inbox.
test.describe('owner journey', () => {
    test.beforeAll(reseed);

    test('from sign-in to a handled conversation', async ({ page, lang, t }) => {
        await signIn(page, lang, t, 'owner@demo.test');

        // One workspace: straight into onboarding, in the right direction.
        await expect(page).toHaveURL(new RegExp(`/${lang}/t/[^/]+/onboarding$`));
        await expect(page.locator('html')).toHaveAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
        await expect(page.getByRole('heading', { name: t.onboarding.title })).toBeVisible();

        // The checklist is green except margins.
        const checklist = page.getByTestId('go-live-checklist');
        await expect(checklist.locator('[data-ok="false"]')).toHaveCount(1);
        await expect(checklist.getByText(t.onboarding.checklist.items.margins_entered)).toBeVisible();
        await expect(page.getByText(t.onboarding.whatsapp.connected).last()).toBeVisible();

        // Enter margins → checklist turns fully green.
        const margins = page.getByTestId('margins-form');
        await margins.getByLabel(t.onboarding.margins.default).fill('40');
        await margins.getByLabel('عود').fill('55');
        await margins.getByRole('button', { name: t.common.save }).click();
        await expect(margins.getByText(t.common.saved)).toBeVisible();
        await expect(checklist.locator('[data-ok="false"]')).toHaveCount(0);

        // Recovery: enable, then the kill switch is offered.
        await page.getByRole('link', { name: t.onboarding.checklist.goToRecovery }).click();
        await expect(page.getByTestId('recovery-state')).toHaveAttribute('data-enabled', 'false');
        await page.getByRole('button', { name: t.recovery.enable }).click();
        await expect(page.getByTestId('recovery-state')).toHaveAttribute('data-enabled', 'true');
        await expect(page.getByRole('button', { name: t.recovery.pause })).toBeVisible();

        // Settings refuse unsafe bounds: under 60 minutes never submits.
        const settings = page.getByTestId('recovery-settings');
        await settings.getByLabel(t.recovery.firstDelay).fill('30');
        await settings.getByRole('button', { name: t.common.save }).click();
        expect(await settings.locator('#first_delay_minutes').evaluate((input: HTMLInputElement) => input.validity.rangeUnderflow)).toBe(true);
        await expect(settings.getByText(t.common.saved)).toHaveCount(0);
        await settings.getByLabel(t.recovery.firstDelay).fill('90');
        await settings.getByRole('button', { name: t.common.save }).click();
        await expect(settings.getByText(t.common.saved)).toBeVisible();

        // Report: never a lone number — a verdict and ranges or "not enough data".
        await page.getByRole('link', { name: t.nav.report }).click();
        await expect(page.getByTestId('report-verdict')).toBeVisible();
        await expect(page.getByText(t.report.incrementalProfit)).toBeVisible();

        // Approvals: the gold button approves the discount.
        await page.getByRole('link', { name: t.nav.approvals }).click();
        await expect(page.getByTestId('approvals').getByText('15%')).toBeVisible();
        await page.getByRole('button', { name: t.approvals.approve }).click();
        await expect(page.getByText(t.approvals.empty)).toBeVisible();

        // Inbox: take the conversation, leave a note, close it.
        await page.getByRole('link', { name: t.nav.inbox }).click();
        // Recovery is on, so whoever holds the kill switch sees it here too (H7).
        await expect(page.getByRole('button', { name: t.recovery.pause })).toBeVisible();
        await page.getByTestId('inbox-list').getByRole('link').first().click();
        await expect(page.getByText('هل يوجد توصيل لجدة اليوم؟')).toBeVisible();
        await page.getByRole('button', { name: t.inbox.take }).click();
        await expect(page.getByRole('button', { name: t.inbox.release })).toBeVisible();
        await page.getByPlaceholder(t.inbox.notePlaceholder).fill('اتصلت بالعميل، التوصيل غدًا.');
        await page.getByRole('button', { name: t.inbox.addNote }).click();
        await expect(page.getByText('اتصلت بالعميل، التوصيل غدًا.')).toBeVisible();
        await page.getByRole('button', { name: t.inbox.filter.closed }).click();
        await expect(page.getByRole('button', { name: t.inbox.filter.closed })).toHaveAttribute('aria-pressed', 'true');

        // Usage for this month.
        await page.getByRole('link', { name: t.nav.usage }).click();
        await expect(page.getByTestId('usage-table').getByText('37')).toBeVisible();

        // Kill switch, with confirmation.
        await page.getByRole('link', { name: t.nav.recovery }).click();
        await page.getByRole('button', { name: t.recovery.pause }).click();
        await page.getByTestId('confirm').click();
        await expect(page.getByTestId('recovery-state')).toHaveAttribute('data-enabled', 'false');

        // Sign out → back to login; the app is closed again.
        await page.getByRole('button', { name: t.common.signOut }).click();
        await expect(page).toHaveURL(new RegExp(`/${lang}/login$`));
        await page.goBack();
        await expect(page).toHaveURL(new RegExp(`/${lang}/login$`));
    });
});
