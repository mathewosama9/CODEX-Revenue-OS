import { execSync } from 'node:child_process';

import { expect, reseed, signIn, test } from './fixtures';

/** The customer writes on WhatsApp (local-only command, real webhook path, demo model). */
function customerSays(text: string) {
    execSync(`php artisan codex:demo-message ${JSON.stringify(text)}`, {
        cwd: '..',
        env: { ...process.env, DB_DATABASE: 'codex_e2e', CODEX_AI_PROVIDER: 'demo', CODEX_EMBEDDINGS_ENABLED: 'false' },
        stdio: 'ignore',
    });
}

test.describe('AI agent in the inbox (E12b)', () => {
    test.beforeEach(reseed);

    test('a draft waits for a member, and after takeover the AI writes nothing, even for 10 messages', async ({ page, lang, t }) => {
        customerSays('بكم العود الكمبودي؟');

        await signIn(page, lang, t, 'agent@demo.test');
        await page.getByRole('navigation').getByRole('link', { name: t.nav.inbox }).click();
        await page.getByTestId('inbox-list').getByRole('link').first().click();

        const panel = page.getByTestId('ai-panel');
        await expect(panel.getByTestId('ai-draft')).toHaveAttribute('data-status', 'pending');
        await expect(panel.getByTestId('ai-log').getByRole('listitem')).toHaveCount(1);

        await panel.getByRole('button', { name: t.ai.takeover }).click();
        await expect(panel.getByTestId('ai-state')).toHaveAttribute('data-paused', 'true');
        await expect(panel.getByTestId('ai-draft')).toHaveCount(0);

        for (let i = 1; i <= 10; i++) {
            customerSays(`رسالة ${i}: بكم العود؟`);
        }

        await page.reload();
        await expect(page.getByTestId('conversation-thread').getByText('رسالة 10: بكم العود؟')).toBeVisible();
        await expect(panel.getByTestId('ai-log').getByRole('listitem')).toHaveCount(1);
        await expect(panel.getByTestId('ai-draft')).toHaveCount(0);
        await expect(page.getByTestId('ai-message')).toHaveCount(0);

        await panel.getByRole('button', { name: t.ai.handBack }).click();
        await expect(panel.getByTestId('ai-state')).toHaveAttribute('data-paused', 'false');
    });

    test('a discount request goes to a person before the AI, with the reason shown', async ({ page, lang, t }) => {
        customerSays('فيه خصم على العود؟');

        await signIn(page, lang, t, 'agent@demo.test');
        await page.getByRole('navigation').getByRole('link', { name: t.nav.inbox }).click();
        await expect(page.getByTestId('inbox-list')).toContainText(t.ai.reason.discount_request);
    });

    test('the drafts screen lists the pending reply and a member can reject it', async ({ page, lang, t }) => {
        customerSays('عندكم بخور للمجلس؟');

        await signIn(page, lang, t, 'agent@demo.test');
        await page.getByRole('navigation').getByRole('link', { name: t.nav.drafts }).click();
        const draft = page.getByTestId('drafts-list').getByTestId('ai-draft').first();
        await expect(draft).toHaveAttribute('data-status', 'pending');
        await draft.getByRole('button', { name: t.ai.reject }).click();
        await expect(page.getByTestId('drafts-list').getByTestId('ai-draft')).toHaveCount(0);
    });
});
