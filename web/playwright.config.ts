import { defineConfig, devices } from '@playwright/test';

// The E2E suite runs against the real Laravel API (not mocks), on its own
// database (codex_e2e) and ports, so it never touches your dev data.
const API_PORT = 8010;
const WEB_PORT = 3010;

export default defineConfig<{ lang: 'ar' | 'en' }>({
    testDir: './e2e',
    // One database, stateful journeys: run them one after another.
    workers: 1,
    // The owner journey walks every screen in one test.
    timeout: 90_000,
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL: `http://localhost:${WEB_PORT}`,
        trace: 'retain-on-failure',
    },
    projects: [
        { name: 'rtl-ar', use: { ...devices['Desktop Chrome'], lang: 'ar' } },
        { name: 'ltr-en', use: { ...devices['Desktop Chrome'], lang: 'en' } },
    ],
    webServer: [
        {
            // php -S instead of `artisan serve`, which drops these env overrides.
            command: `php -S localhost:${API_PORT} ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`,
            cwd: '../public',
            url: `http://localhost:${API_PORT}/up`,
            reuseExistingServer: !process.env.CI,
            env: {
                DB_DATABASE: 'codex_e2e',
                APP_URL: `http://localhost:${API_PORT}`,
                FRONTEND_URL: `http://localhost:${WEB_PORT}`,
                SANCTUM_STATEFUL_DOMAINS: `localhost:${WEB_PORT}`,
            },
        },
        {
            command: process.env.CI ? `npx next build && npx next start -p ${WEB_PORT}` : `npx next dev -p ${WEB_PORT}`,
            url: `http://localhost:${WEB_PORT}/ar/login`,
            reuseExistingServer: !process.env.CI,
            timeout: 180_000,
            env: { NEXT_PUBLIC_API_URL: `http://localhost:${API_PORT}` },
        },
    ],
});
