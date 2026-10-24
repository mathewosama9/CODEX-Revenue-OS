'use client';

import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { Suspense, useState } from 'react';

import { AuthHeading } from '@/components/auth-shell';
import { Field } from '@/components/field';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { api } from '@/lib/api';
import { useHref } from '@/lib/session';
import { useSubmit } from '@/lib/use-form';

// The emailed link carries ?token=…&email=… (Fortify password broker).
function ResetForm() {
    const { t } = useI18n();
    const href = useHref();
    const params = useSearchParams();
    const { busy, errors, failed, run } = useSubmit();
    const [password, setPassword] = useState('');
    const [confirmation, setConfirmation] = useState('');
    const [done, setDone] = useState(false);
    const token = params.get('token');
    const email = params.get('email');

    if (!token || !email) {
        return (
            <Alert variant="destructive">
                <AlertDescription>{t.auth.resetInvalid}</AlertDescription>
            </Alert>
        );
    }

    if (done) {
        return (
            <Alert>
                <AlertDescription>
                    {t.auth.resetDone}{' '}
                    <Link href={href('/login')} className="text-link hover:underline">
                        {t.auth.loginTitle}
                    </Link>
                </AlertDescription>
            </Alert>
        );
    }

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        void run(async () => {
            await api('POST', '/reset-password', {
                token,
                email,
                password,
                password_confirmation: confirmation,
            });
            setDone(true);
        });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
            <Field
                id="password"
                type="password"
                dir="ltr"
                autoComplete="new-password"
                label={t.auth.newPassword}
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                error={errors.password ?? errors.email}
            />
            <Field
                id="password_confirmation"
                type="password"
                dir="ltr"
                autoComplete="new-password"
                label={t.auth.passwordConfirm}
                value={confirmation}
                onChange={(event) => setConfirmation(event.target.value)}
            />
            {failed && (
                <Alert variant="destructive">
                    <AlertDescription>{t.common.error}</AlertDescription>
                </Alert>
            )}
            <Button type="submit" disabled={busy}>
                {t.auth.reset}
            </Button>
        </form>
    );
}

export default function ResetPasswordPage() {
    const { t } = useI18n();

    return (
        <>
            <AuthHeading title={t.auth.resetTitle} />
            <Suspense>
                <ResetForm />
            </Suspense>
        </>
    );
}
