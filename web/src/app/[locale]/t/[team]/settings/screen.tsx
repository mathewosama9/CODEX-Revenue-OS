'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { ConfirmButton } from '@/components/confirm-button';
import { Field } from '@/components/field';
import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { Section } from '@/components/section';
import { StatusLine } from '@/components/status-dot';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { api, ApiError, getData, sendData } from '@/lib/api';
import { useMe, useTeam } from '@/lib/session';
import type { Me } from '@/lib/session';
import type { Members } from '@/lib/types';
import { useSubmit } from '@/lib/use-form';

export default function SettingsScreen() {
    const { t } = useI18n();
    const { me, can } = useTeam();

    return (
        <>
            <PageHeader title={t.settings.title} />
            {me && <ProfileSection me={me} />}
            <PasswordSection />
            {me && <TwoFactorSection enabled={me.two_factor_enabled} />}
            {can('invitation:create') && <TeamSection />}
        </>
    );
}

function ProfileSection({ me }: { me: Me }) {
    const { t } = useI18n();
    const client = useQueryClient();
    const { busy, errors, failed, run } = useSubmit();
    const [name, setName] = useState(me.name);
    const [email, setEmail] = useState(me.email);
    const [saved, setSaved] = useState(false);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        setSaved(false);

        void run(async () => {
            client.setQueryData(['me'], await sendData<Me>('PATCH', '/me/profile', { name, email }));
            setSaved(true);
        });
    };

    return (
        <Section title={t.settings.profile}>
            <form onSubmit={submit} className="flex max-w-sm flex-col gap-4" noValidate>
                <Field id="name" label={t.auth.name} autoComplete="name" value={name} onChange={(event) => setName(event.target.value)} error={errors.name} />
                <Field
                    id="email"
                    type="email"
                    dir="ltr"
                    autoComplete="email"
                    label={t.auth.email}
                    value={email}
                    onChange={(event) => setEmail(event.target.value)}
                    error={errors.email}
                    help={me.email_verified ? undefined : t.settings.emailUnverified}
                />
                {failed && <p className="text-sm text-destructive">{t.common.error}</p>}
                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={busy}>
                        {t.common.save}
                    </Button>
                    {saved && <span className="text-sm text-gain">{t.settings.profileSaved}</span>}
                </div>
            </form>
        </Section>
    );
}

function PasswordSection() {
    const { t } = useI18n();
    const { busy, errors, failed, run } = useSubmit();
    const empty = { current_password: '', password: '', password_confirmation: '' };
    const [form, setForm] = useState(empty);
    const [saved, setSaved] = useState(false);

    const set = (field: keyof typeof form) => (event: React.ChangeEvent<HTMLInputElement>) =>
        setForm({ ...form, [field]: event.target.value });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        setSaved(false);

        void run(async () => {
            await api('PUT', '/api/v1/me/password', form);
            setForm(empty);
            setSaved(true);
        });
    };

    return (
        <Section title={t.settings.password}>
            <form onSubmit={submit} className="flex max-w-sm flex-col gap-4" noValidate>
                <Field id="current_password" type="password" dir="ltr" autoComplete="current-password" label={t.settings.currentPassword} value={form.current_password} onChange={set('current_password')} error={errors.current_password} />
                <Field id="new_password" type="password" dir="ltr" autoComplete="new-password" label={t.auth.newPassword} value={form.password} onChange={set('password')} error={errors.password} />
                <Field id="password_confirmation" type="password" dir="ltr" autoComplete="new-password" label={t.auth.passwordConfirm} value={form.password_confirmation} onChange={set('password_confirmation')} />
                {failed && <p className="text-sm text-destructive">{t.common.error}</p>}
                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={busy}>
                        {t.common.save}
                    </Button>
                    {saved && <span className="text-sm text-gain">{t.settings.passwordSaved}</span>}
                </div>
            </form>
        </Section>
    );
}

type TwoFactorStep =
    | { kind: 'idle' }
    | { kind: 'confirm-password'; then: () => Promise<void> }
    | { kind: 'scan'; qr: string; secret: string }
    | { kind: 'codes'; codes: string[] };

/**
 * Fortify's two-factor routes (/user/*). They answer 423 until the password
 * was confirmed recently; then we ask for it and retry the same step.
 */
function TwoFactorSection({ enabled }: { enabled: boolean }) {
    const { t } = useI18n();
    const client = useQueryClient();
    const { busy, errors, failed, run } = useSubmit();
    const [step, setStep] = useState<TwoFactorStep>({ kind: 'idle' });
    const [password, setPassword] = useState('');
    const [code, setCode] = useState('');

    const withPassword = (action: () => Promise<void>) =>
        run(async () => {
            try {
                await action();
            } catch (error) {
                if (error instanceof ApiError && error.status === 423) {
                    setStep({ kind: 'confirm-password', then: action });

                    return;
                }

                throw error;
            }
        });

    const startEnable = async () => {
        await api('POST', '/user/two-factor-authentication');
        const qr = await api<{ svg: string }>('GET', '/user/two-factor-qr-code');
        const secret = await api<{ secretKey: string }>('GET', '/user/two-factor-secret-key');
        setStep({ kind: 'scan', qr: qr.svg, secret: secret.secretKey });
    };

    const disable = async () => {
        await api('DELETE', '/user/two-factor-authentication');
        await client.invalidateQueries({ queryKey: ['me'] });
        setStep({ kind: 'idle' });
    };

    const finish = async () => {
        await api('POST', '/user/confirmed-two-factor-authentication', { code: code.trim() });
        const codes = await api<string[]>('GET', '/user/two-factor-recovery-codes');
        setCode('');
        setStep({ kind: 'codes', codes });
    };

    return (
        <Section title={t.settings.twoFactor} subtitle={t.settings.twoFactorHelp} testId="two-factor">
            <StatusLine ok={enabled} label={enabled ? t.settings.twoFactorOn : t.settings.twoFactorOff} />

            {step.kind === 'idle' &&
                (enabled ? (
                    <Button variant="outline" className="self-start" disabled={busy} onClick={() => void withPassword(disable)}>
                        {t.settings.disable}
                    </Button>
                ) : (
                    <Button className="self-start" disabled={busy} onClick={() => void withPassword(startEnable)}>
                        {t.settings.enable}
                    </Button>
                ))}

            {step.kind === 'confirm-password' && (
                <form
                    className="flex max-w-sm flex-col gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void withPassword(async () => {
                            await api('POST', '/user/confirm-password', { password });
                            setPassword('');
                            await step.then();
                        });
                    }}
                >
                    <p className="text-sm text-muted-foreground">{t.settings.confirmPasswordHelp}</p>
                    <Field id="confirm_password" type="password" dir="ltr" autoComplete="current-password" label={t.settings.confirmPasswordTitle} value={password} onChange={(event) => setPassword(event.target.value)} error={errors.password} />
                    <Button type="submit" disabled={busy || password === ''}>
                        {t.settings.confirm}
                    </Button>
                </form>
            )}

            {step.kind === 'scan' && (
                <form
                    className="flex max-w-sm flex-col gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void run(finish);
                    }}
                >
                    <p className="text-sm text-muted-foreground">{t.settings.scanQr}</p>
                    {/* The SVG comes from our API; shown as an image so it can't run anything. */}
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img
                        alt=""
                        className="size-48 rounded-md bg-white p-2"
                        src={`data:image/svg+xml;charset=utf-8,${encodeURIComponent(step.qr)}`}
                    />
                    <p className="text-xs text-muted-foreground">
                        {t.settings.setupKey}: <code dir="ltr" className="font-numeric">{step.secret}</code>
                    </p>
                    <Field id="two_factor_code" dir="ltr" inputMode="numeric" autoComplete="one-time-code" className="font-numeric tracking-widest" label={t.auth.code} value={code} onChange={(event) => setCode(event.target.value)} error={errors.code} />
                    <Button type="submit" disabled={busy || code.trim() === ''}>
                        {t.auth.verify}
                    </Button>
                </form>
            )}

            {step.kind === 'codes' && (
                <div className="flex max-w-sm flex-col gap-3">
                    <p className="text-sm font-medium">{t.settings.recoveryCodes}</p>
                    <p className="text-sm text-muted-foreground">{t.settings.recoveryHelp}</p>
                    <ul dir="ltr" className="grid grid-cols-2 gap-1 rounded-md bg-muted p-3 font-numeric text-sm">
                        {step.codes.map((recovery) => (
                            <li key={recovery}>{recovery}</li>
                        ))}
                    </ul>
                    <Button
                        className="self-start"
                        onClick={() => {
                            setStep({ kind: 'idle' });
                            void client.invalidateQueries({ queryKey: ['me'] });
                        }}
                    >
                        {t.settings.done}
                    </Button>
                </div>
            )}

            {failed && <p className="text-sm text-destructive">{t.common.error}</p>}
        </Section>
    );
}

function TeamSection() {
    const { t } = useI18n();
    const { path, slug, can } = useTeam();
    const { data: me } = useMe();
    const client = useQueryClient();
    const { busy, errors, failed, run } = useSubmit();
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);
    const key = [slug, 'members'];

    const members = useQuery({ queryKey: key, queryFn: () => getData<Members>(path('/members')) });

    const change = useMutation({
        mutationFn: (target: string) => sendData<Members>('DELETE', path(target)),
        onSuccess: (data) => client.setQueryData(key, data),
    });

    const invite = (event: React.FormEvent) => {
        event.preventDefault();
        setSent(false);

        void run(async () => {
            client.setQueryData(key, await sendData<Members>('POST', path('/invitations'), { email, role: 'agent' }));
            setEmail('');
            setSent(true);
        });
    };

    return (
        <Section title={t.settings.team} subtitle={t.settings.teamHelp} testId="team">
            <QueryState query={members}>
                {(data) => (
                    <div className="flex flex-col gap-5">
                        <ul className="divide-y text-sm">
                            {data.members.map((member) => (
                                <li key={member.id} className="flex flex-wrap items-center justify-between gap-3 py-2">
                                    <span className="flex flex-col">
                                        <span className="font-medium">{member.name}</span>
                                        {member.email && (
                                            <span dir="ltr" className="text-start text-xs text-muted-foreground">
                                                {member.email}
                                            </span>
                                        )}
                                    </span>
                                    <span className="flex items-center gap-3">
                                        <span className="text-xs text-muted-foreground">{t.teams.role[member.role]}</span>
                                        {can('member:remove') && member.role !== 'owner' && member.id !== me?.id && (
                                            <ConfirmButton
                                                size="sm"
                                                variant="outline"
                                                message={t.settings.removeConfirm}
                                                onConfirm={() => change.mutate(`/members/${member.id}`)}
                                            >
                                                {t.settings.remove}
                                            </ConfirmButton>
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>

                        {data.invitations.length > 0 && (
                            <div className="flex flex-col gap-2">
                                <span className="text-sm font-medium">{t.settings.pending}</span>
                                <ul className="divide-y text-sm">
                                    {data.invitations.map((invitation) => (
                                        <li key={invitation.id} className="flex items-center justify-between gap-3 py-2">
                                            <span dir="ltr" className="text-start">{invitation.email}</span>
                                            {can('invitation:cancel') && (
                                                <Button size="sm" variant="ghost" disabled={change.isPending} onClick={() => change.mutate(`/invitations/${invitation.id}`)}>
                                                    {t.settings.cancelInvite}
                                                </Button>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        <form onSubmit={invite} className="flex max-w-sm flex-col gap-3" noValidate>
                            <Field id="invite_email" type="email" dir="ltr" autoComplete="off" label={t.auth.email} value={email} onChange={(event) => setEmail(event.target.value)} error={errors.email ?? errors.role} />
                            {(failed || change.isError) && <p className="text-sm text-destructive">{t.common.error}</p>}
                            <div className="flex items-center gap-3">
                                <Button type="submit" disabled={busy || email === ''}>
                                    {t.settings.invite}
                                </Button>
                                {sent && <span className="text-sm text-gain">{t.settings.inviteSent}</span>}
                            </div>
                        </form>
                    </div>
                )}
            </QueryState>
        </Section>
    );
}
