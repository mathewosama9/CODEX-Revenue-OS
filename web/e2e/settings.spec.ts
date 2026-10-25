import { expect, reseed, signIn, test } from './fixtures';

test.describe('settings', () => {
    test.beforeAll(reseed);

    test('profile, password errors in the page language, two-factor setup, inviting an agent', async ({ page, lang, t }) => {
        await signIn(page, lang, t, 'owner@demo.test');
        await page.getByRole('link', { name: t.nav.settings }).click();

        // Server validation comes back in the language of the page.
        await page.getByLabel(t.settings.currentPassword).fill('wrong-password');
        await page.getByLabel(t.auth.newPassword).fill('N3w-password!');
        await page.getByLabel(t.auth.passwordConfirm, { exact: true }).fill('N3w-password!');
        await page.getByRole('button', { name: t.common.save }).nth(1).click();
        await expect(page.getByText(lang === 'ar' ? 'كلمة المرور غير صحيحة.' : 'The password is incorrect.')).toBeVisible();

        // Profile.
        await page.getByLabel(t.auth.name).fill('ريم العتيبي');
        await page.getByRole('button', { name: t.common.save }).first().click();
        await expect(page.getByText(t.settings.profileSaved)).toBeVisible();

        // Two-factor: asks for the password, then shows the QR code.
        const twoFactor = page.getByTestId('two-factor');
        await twoFactor.getByRole('button', { name: t.settings.enable }).click();
        await twoFactor.getByLabel(t.settings.confirmPasswordTitle).fill('password');
        await twoFactor.getByRole('button', { name: t.settings.confirm }).click();
        await expect(twoFactor.getByText(t.settings.scanQr)).toBeVisible();

        // Team: invite an agent.
        const team = page.getByTestId('team');
        await team.getByLabel(t.auth.email).fill(`new.agent.${lang}@demo.test`);
        await team.getByRole('button', { name: t.settings.invite }).click();
        await expect(team.getByText(t.settings.inviteSent)).toBeVisible();
        await expect(team.getByText(`new.agent.${lang}@demo.test`)).toBeVisible();
    });
});
