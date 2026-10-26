import { expect, reseed, setWorkspaceCurrency, signIn, test } from './fixtures';

// ADR-012: a workspace in Egyptian pounds shows its own currency everywhere money
// appears, and money inputs keep piasters exactly. The marketing site stays in riyals.
const EGP = { ar: 'ج.م', en: 'EGP' } as const;
const SAR = { ar: 'ر.س', en: 'SAR' } as const;
const format = (label: string, values: { currency: string }) => label.replace('{currency}', values.currency);

// A conclusive, positive estimate (minor units of the workspace currency), so the gold figure appears.
const HELPFUL_REPORT = {
    data: {
        status: 'ok',
        period: { from: '2026-09-28', to: '2026-10-25' },
        method_version: 'e2e',
        outcome_window_days: 7,
        holdout_bps: 1000,
        sample: { treatment: 900, control: 100, not_yet_matured: 0 },
        conversion: { treatment: 0.12, control: 0.08 },
        attributed_revenue_halalas: 900_050,
        incremental_revenue_halalas: { low: 200_000, point: 350_000, high: 500_000, win_probability: 0.97, verdict: 'helpful' },
        incremental_profit_halalas: { low: 120_000, point: 300_000, high: 480_000, win_probability: 0.96, verdict: 'helpful' },
        costs_halalas: { meta_message: 20_000, discount: 10_000, llm: 0, total: 30_000 },
        margin_quality: { revenue_on_assumed_margin_share: 0, warning: false },
        verdict: 'helpful',
        additional_customers_needed: 0,
    },
};

test.describe('a workspace in Egyptian pounds', () => {
    test.beforeAll(() => {
        reseed();
        setWorkspaceCurrency('EGP', 'Africa/Cairo');
    });

    test('money reads in pounds, one gold figure', async ({ page, lang, t }) => {
        await page.route(/\/api\/v1\/teams\/[^/]+\/reports\/profit\?/, (route) => route.fulfill({ json: HELPFUL_REPORT }));
        await signIn(page, lang, t, 'owner@demo.test');
        await expect(page).toHaveURL(new RegExp(`/${lang}/t/[^/]+/overview$`));

        const profit = page.getByTestId('overview-profit');
        await expect(profit.locator('.text-revenue-ink')).toContainText(EGP[lang]);
        await expect(profit).not.toContainText(SAR[lang]);
        await expect(page.locator('.text-revenue-ink:visible')).toHaveCount(1);

        // The report: exact piasters where they exist (9,000.50), whole pounds for ranges.
        await page.getByRole('link', { name: t.nav.report }).click();
        const attributed = lang === 'ar' ? `9,000.50 ${EGP.ar}` : `${EGP.en} 9,000.50`;
        await expect(page.getByText(attributed)).toBeVisible();
        await expect(page.getByRole('main')).not.toContainText(SAR[lang]);

        // Approvals: the waiting cart (42000 minor units) in pounds.
        await page.getByRole('link', { name: t.nav.approvals }).click();
        const cart = lang === 'ar' ? `420 ${EGP.ar}` : `${EGP.en} 420`;
        await expect(page.getByTestId('approvals')).toContainText(cart);
    });

    test('money settings are labelled in pounds and keep piasters', async ({ page, lang, t }) => {
        await signIn(page, lang, t, 'owner@demo.test');
        await expect(page).toHaveURL(new RegExp(`/${lang}/t/[^/]+/overview$`));

        await page.getByRole('link', { name: t.nav.recovery }).click();
        const settings = page.getByTestId('recovery-settings');
        const minCart = settings.getByLabel(format(t.recovery.minCart, { currency: EGP[lang] }));
        // The seeded budget: 100000 minor units = 1000 pounds.
        await expect(settings.getByLabel(format(t.recovery.budget, { currency: EGP[lang] }))).toHaveValue('1000');
        await minCart.fill('25.50');
        await settings.getByRole('button', { name: t.common.save }).click();
        await expect(settings.getByText(t.common.saved)).toBeVisible();
        await page.reload();
        await expect(page.getByTestId('recovery-settings').getByLabel(format(t.recovery.minCart, { currency: EGP[lang] }))).toHaveValue('25.50');

        await page.goto(page.url().replace(/\/recovery$/, '/ai'));
        const limits = page.getByTestId('ai-limits');
        await limits.getByLabel(format(t.ai.settings.handoffCart, { currency: EGP[lang] })).fill('2500.75');
        await limits.getByRole('button', { name: t.common.save }).click();
        await expect(limits.getByText(t.common.saved)).toBeVisible();
        await page.reload();
        await expect(page.getByTestId('ai-limits').getByLabel(format(t.ai.settings.handoffCart, { currency: EGP[lang] }))).toHaveValue('2500.75');
    });

    test('the marketing site stays in riyals', async ({ page, lang }) => {
        await page.goto(`/${lang}`);
        await expect(page.locator('body')).toContainText(SAR[lang]);
        await expect(page.locator('body')).not.toContainText(EGP[lang]);
    });
});
