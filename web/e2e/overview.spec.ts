import { expect, reseed, signIn, test } from './fixtures';

// The overview: one card per screen the member may open, setup first while it is
// incomplete, and at most one gold figure (the incremental profit range).
const OWNER_CARDS = [
    'overview-setup',
    'overview-profit',
    'overview-recovery',
    'overview-approvals',
    'overview-drafts',
    'overview-inbox',
    'overview-ai',
    'overview-usage',
    'overview-soon',
];
const AGENT_CARDS = ['overview-drafts', 'overview-inbox', 'overview-soon'];

// A conclusive, positive estimate, so the gold figure appears (the demo store has too little data).
const HELPFUL_REPORT = {
    data: {
        status: 'ok',
        period: { from: '2026-09-28', to: '2026-10-25' },
        method_version: 'e2e',
        outcome_window_days: 7,
        holdout_bps: 1000,
        sample: { treatment: 900, control: 100, not_yet_matured: 0 },
        conversion: { treatment: 0.12, control: 0.08 },
        attributed_revenue_halalas: 900_000,
        incremental_revenue_halalas: { low: 200_000, point: 350_000, high: 500_000, win_probability: 0.97, verdict: 'helpful' },
        incremental_profit_halalas: { low: 120_000, point: 300_000, high: 480_000, win_probability: 0.96, verdict: 'helpful' },
        costs_halalas: { meta_message: 20_000, discount: 10_000, llm: 0, total: 30_000 },
        margin_quality: { revenue_on_assumed_margin_share: 0, warning: false },
        verdict: 'helpful',
        additional_customers_needed: 0,
    },
};

test.describe('overview', () => {
    test.beforeAll(reseed);

    test('the owner sees every card, setup first', async ({ page, lang, t }) => {
        await signIn(page, lang, t, 'owner@demo.test');
        await expect(page).toHaveURL(new RegExp(`/${lang}/t/[^/]+/overview$`));
        await expect(page.getByRole('heading', { name: t.overview.title })).toBeVisible();

        for (const card of OWNER_CARDS) {
            await expect(page.getByTestId(card)).toBeVisible();
        }

        const overview = page.getByTestId('overview');
        await expect(overview.locator(':scope > *').first()).toHaveAttribute('data-testid', 'overview-setup');

        // The demo store: margins missing, one discount and one conversation waiting, recovery off.
        await expect(page.getByTestId('overview-approvals').getByText(t.overview.approvals.waiting.replace('{n}', '1'))).toBeVisible();
        await expect(page.getByTestId('overview-inbox').getByText(t.overview.inbox.open.replace('{n}', '1'))).toBeVisible();
        await expect(page.getByTestId('overview-recovery').locator('[data-enabled="false"]')).toBeVisible();
        // Too little data: said plainly, never a lone number.
        await expect(page.getByTestId('overview-profit-collecting')).toBeVisible();

        // Each card opens its screen.
        await page.getByTestId('overview-inbox').getByRole('link').click();
        await expect(page).toHaveURL(new RegExp(`/${lang}/t/[^/]+/inbox$`));
    });

    test('exactly one gold figure: the incremental profit', async ({ page, lang, t }) => {
        await page.route(/\/api\/v1\/teams\/[^/]+\/reports\/profit\?/, (route) => route.fulfill({ json: HELPFUL_REPORT }));
        await signIn(page, lang, t, 'owner@demo.test');
        await expect(page).toHaveURL(new RegExp(`/${lang}/t/[^/]+/overview$`));

        await expect(page.getByTestId('overview-profit').locator('.text-revenue-ink')).toBeVisible();
        await expect(page.locator('.text-revenue-ink:visible')).toHaveCount(1);
    });

    test('an agent only sees the cards they may open', async ({ page, lang, t }) => {
        await signIn(page, lang, t, 'agent@demo.test');
        await expect(page).toHaveURL(new RegExp(`/${lang}/t/[^/]+/overview$`));

        for (const card of AGENT_CARDS) {
            await expect(page.getByTestId(card)).toBeVisible();
        }

        for (const card of OWNER_CARDS.filter((card) => !AGENT_CARDS.includes(card))) {
            await expect(page.getByTestId(card)).toHaveCount(0);
        }

        // Nothing it may not read is even asked for, so no "forbidden" notice either.
        await expect(page.getByText(t.common.forbidden)).toHaveCount(0);
    });
});
