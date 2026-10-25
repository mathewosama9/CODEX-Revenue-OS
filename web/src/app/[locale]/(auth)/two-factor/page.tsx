'use client';

import { useQueryClient } from '@tanstack/react-query';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useState } from 'react';

import { AuthHeading } from '@/components/auth-shell';
import { Field } from '@/components/field';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { api, ApiError, REDIRECTED } from '@/lib/api';
import { afterSignIn, useHref } from '@/lib/session';
import { useSubmit } from '@/lib/use-form';

export default function TwoFactorPage() {
    const { t } = useI18n();
    const href = useHref();
    const router = useRouter();
    const client = useQueryClient();
    const { busy, errors, failed, failedStatus, run } = useSubmit();
    const [recovery, setRecovery] = useState(false);
    const [value, setValue] = useState('');
    const field = recovery ? 'recovery_code' : 'code';

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        void run(async () => {
            try {
                await api('POST', '/two-factor-challenge', { [field]: value.trim() });
            } catch (error) {
                // No pending sign-in any more (it expired): Fortify redirects (review L9).
                if (error instanceof ApiError && error.status === REDIRECTED) {
                    router.replace(href('/login?expired=challenge'));

                    return;
                }

                throw error;
            }

            await afterSignIn(client);
            router.replace(href(''));
        });
    };

    return (
        <>
            <AuthHeading
                title={t.auth.twoFactorTitle}
                subtitle={recovery ? t.auth.recoverySubtitle : t.auth.twoFactorSubtitle}
            />
            <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                <Field
                    id={field}
                    dir="ltr"
                    autoComplete="one-time-code"
                    inputMode={recovery ? 'text' : 'numeric'}
                    className="font-numeric tracking-widest"
                    label={recovery ? t.auth.recoveryCode : t.auth.code}
                    value={value}
                    onChange={(event) => setValue(event.target.value)}
                    error={errors[field]}
                    autoFocus
                />
                {failed && (
                    <Alert variant="destructive">
                        <AlertDescription>{failedStatus === 429 ? t.auth.tooManyAttempts : t.common.error}</AlertDescription>
                    </Alert>
                )}
                <Button type="submit" disabled={busy || value.trim() === ''}>
                    {t.auth.verify}
                </Button>
                <button
                    type="button"
                    className="text-start text-sm text-link hover:underline"
                    onClick={() => {
                        setRecovery(!recovery);
                        setValue('');
                    }}
                >
                    {recovery ? t.auth.useCode : t.auth.useRecovery}
                </button>
                <Link href={href('/login')} className="text-sm text-muted-foreground hover:underline">
                    {t.auth.backToLogin}
                </Link>
            </form>
        </>
    );
}
