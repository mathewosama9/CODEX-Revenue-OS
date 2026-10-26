'use client';

import { useQueryClient } from '@tanstack/react-query';
import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { Suspense, useState } from 'react';

import { AuthHeading } from '@/components/auth-shell';
import { Field } from '@/components/field';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { api, ApiError, ensureCsrf, REDIRECTED } from '@/lib/api';
import { afterSignIn, useHref } from '@/lib/session';
import { useSubmit } from '@/lib/use-form';

export default function LoginPage() {
    const { t } = useI18n();
    const href = useHref();
    const router = useRouter();
    const client = useQueryClient();
    const { busy, errors, failed, failedStatus, run } = useSubmit();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(false);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        void run(async () => {
            await ensureCsrf(true);

            const credentials = { email, password, remember };
            let result: { two_factor?: boolean };

            try {
                result = await api<{ two_factor?: boolean }>('POST', '/login', credentials);
            } catch (error) {
                // Laravel redirects a guest route when an older session is still
                // alive, possibly another account's. End it and sign in as typed (review H9).
                if (!(error instanceof ApiError && error.status === REDIRECTED)) {
                    throw error;
                }

                await api('POST', '/logout').catch(() => undefined);
                await ensureCsrf(true);
                result = await api<{ two_factor?: boolean }>('POST', '/login', credentials);
            }

            if (result?.two_factor) {
                router.push(href('/two-factor'));

                return;
            }

            await afterSignIn(client);
            router.replace(href('/workspaces'));
        });
    };

    return (
        <>
            <AuthHeading title={t.auth.loginTitle} subtitle={t.auth.loginSubtitle} />
            <Suspense>
                <ExpiredNotice />
            </Suspense>
            <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                <Field
                    id="email"
                    type="email"
                    dir="ltr"
                    autoComplete="email"
                    label={t.auth.email}
                    value={email}
                    onChange={(event) => setEmail(event.target.value)}
                    error={errors.email}
                    required
                />
                <Field
                    id="password"
                    type="password"
                    dir="ltr"
                    autoComplete="current-password"
                    label={t.auth.password}
                    value={password}
                    onChange={(event) => setPassword(event.target.value)}
                    error={errors.password}
                    required
                />
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={remember}
                        onChange={(event) => setRemember(event.target.checked)}
                        className="size-4 accent-primary"
                    />
                    {t.auth.remember}
                </label>
                {failed && (
                    <Alert variant="destructive">
                        <AlertDescription>{failedStatus === 429 ? t.auth.tooManyAttempts : t.common.error}</AlertDescription>
                    </Alert>
                )}
                <Button type="submit" disabled={busy}>
                    {t.auth.login}
                </Button>
                <div className="flex flex-col gap-2 text-sm">
                    <Link href={href('/forgot-password')} className="text-link hover:underline">
                        {t.auth.forgotLink}
                    </Link>
                    <span className="text-muted-foreground">
                        {t.auth.noAccount}{' '}
                        <Link href={href('/register')} className="text-link hover:underline">
                            {t.auth.registerLink}
                        </Link>
                    </span>
                </div>
            </form>
        </>
    );
}

/** Shown after the app sent the user back here (?expired=1 or ?expired=challenge). */
function ExpiredNotice() {
    const { t } = useI18n();
    const reason = useSearchParams().get('expired');

    if (reason === null) {
        return null;
    }

    return (
        <Alert className="mb-4" role="status">
            <AlertDescription>{reason === 'challenge' ? t.auth.challengeExpired : t.auth.sessionExpired}</AlertDescription>
        </Alert>
    );
}
