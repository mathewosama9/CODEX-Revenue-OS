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
import { api, ensureCsrf } from '@/lib/api';
import { afterSignIn, useHref } from '@/lib/session';
import { useSubmit } from '@/lib/use-form';

export default function RegisterPage() {
    const { t } = useI18n();
    const href = useHref();
    const router = useRouter();
    const client = useQueryClient();
    const { busy, errors, failed, run } = useSubmit();
    const [form, setForm] = useState({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const set = (field: keyof typeof form) => (event: React.ChangeEvent<HTMLInputElement>) =>
        setForm({ ...form, [field]: event.target.value });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        void run(async () => {
            await ensureCsrf(true);
            await api('POST', '/register', form);
            await afterSignIn(client);
            router.replace(href(''));
        });
    };

    return (
        <>
            <AuthHeading title={t.auth.registerTitle} />
            <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                <Field id="name" label={t.auth.name} autoComplete="name" value={form.name} onChange={set('name')} error={errors.name} />
                <Field id="email" type="email" dir="ltr" autoComplete="email" label={t.auth.email} value={form.email} onChange={set('email')} error={errors.email} />
                <Field id="password" type="password" dir="ltr" autoComplete="new-password" label={t.auth.password} value={form.password} onChange={set('password')} error={errors.password} />
                <Field
                    id="password_confirmation"
                    type="password"
                    dir="ltr"
                    autoComplete="new-password"
                    label={t.auth.passwordConfirm}
                    value={form.password_confirmation}
                    onChange={set('password_confirmation')}
                    error={errors.password_confirmation}
                />
                {failed && (
                    <Alert variant="destructive">
                        <AlertDescription>{t.common.error}</AlertDescription>
                    </Alert>
                )}
                <Button type="submit" disabled={busy}>
                    {t.auth.register}
                </Button>
                <span className="text-sm text-muted-foreground">
                    {t.auth.haveAccount}{' '}
                    <Link href={href('/login')} className="text-link hover:underline">
                        {t.auth.loginTitle}
                    </Link>
                </span>
            </form>
        </>
    );
}
