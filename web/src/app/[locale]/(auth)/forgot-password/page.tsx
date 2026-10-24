'use client';

import Link from 'next/link';
import { useState } from 'react';

import { AuthHeading } from '@/components/auth-shell';
import { Field } from '@/components/field';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { api } from '@/lib/api';
import { useHref } from '@/lib/session';
import { useSubmit } from '@/lib/use-form';

export default function ForgotPasswordPage() {
    const { t } = useI18n();
    const href = useHref();
    const { busy, errors, failed, run } = useSubmit();
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        void run(async () => {
            await api('POST', '/forgot-password', { email });
            setSent(true);
        });
    };

    return (
        <>
            <AuthHeading title={t.auth.forgotTitle} subtitle={t.auth.forgotSubtitle} />
            {sent ? (
                <Alert>
                    <AlertDescription>{t.auth.linkSent}</AlertDescription>
                </Alert>
            ) : (
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
                    />
                    {failed && (
                        <Alert variant="destructive">
                            <AlertDescription>{t.common.error}</AlertDescription>
                        </Alert>
                    )}
                    <Button type="submit" disabled={busy}>
                        {t.auth.sendLink}
                    </Button>
                </form>
            )}
            <Link href={href('/login')} className="mt-4 block text-sm text-link hover:underline">
                {t.auth.backToLogin}
            </Link>
        </>
    );
}
