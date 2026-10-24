import { execSync } from 'node:child_process';

import { test as base, expect } from '@playwright/test';
import type { Page } from '@playwright/test';

import { ar } from '../src/i18n/ar';
import type { Dictionary } from '../src/i18n/ar';
import { en } from '../src/i18n/en';

/** Fresh demo store (database/seeders/DemoStoreSeeder.php) on the E2E database. */
export function reseed() {
    execSync('php artisan migrate:fresh --seed --seeder=DemoStoreSeeder --force', {
        cwd: '..',
        env: { ...process.env, DB_DATABASE: 'codex_e2e' },
        stdio: 'ignore',
    });
}

export const test = base.extend<{ lang: 'ar' | 'en'; t: Dictionary }>({
    lang: ['ar', { option: true }],
    t: async ({ lang }, use) => use(lang === 'ar' ? ar : en),
});

export { expect };

export async function signIn(page: Page, lang: string, t: Dictionary, email: string) {
    await page.goto(`/${lang}/login`);
    await page.getByLabel(t.auth.email, { exact: true }).fill(email);
    await page.getByLabel(t.auth.password, { exact: true }).fill('password');
    await page.getByRole('button', { name: t.auth.login }).click();
}
