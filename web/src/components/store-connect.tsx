'use client';

import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Check, Copy, Download, TriangleAlert } from 'lucide-react';
import { useState } from 'react';

import { ConfirmButton } from '@/components/confirm-button';
import { Field } from '@/components/field';
import { FormErrors } from '@/components/form-errors';
import { QueryState } from '@/components/query-state';
import { StatusLine } from '@/components/status-dot';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { ApiError, getData, sendData } from '@/lib/api';
import { useTeam } from '@/lib/session';
import { formatDateTime } from '@/lib/time';
import type { StoreProvider, StoreStatus, WooConnection } from '@/lib/types';
import { useSubmit } from '@/lib/use-form';
import { cn } from '@/lib/utils';

const PLUGIN_ZIP = '/downloads/codex-connector.zip';

/**
 * The store card for members who manage integrations: Salla (installed from
 * the Salla app store) or WooCommerce (keys here, then the CODEX Connector
 * plugin on the site). The connector secret is shown once, from the answer.
 */
export function StoreConnect() {
    const { t, locale } = useI18n();
    const { path, slug } = useTeam();
    const client = useQueryClient();
    const [secret, setSecret] = useState<WooConnection | null>(null);
    const [failed, setFailed] = useState(false);

    const store = useQuery({
        queryKey: [slug, 'commerce-store'],
        queryFn: () => getData<StoreStatus>(path('/commerce/store')),
    });

    const refresh = () => {
        void client.invalidateQueries({ queryKey: [slug, 'commerce-store'] });
        void client.invalidateQueries({ queryKey: [slug, 'recovery'] });
    };

    const act = async (action: () => Promise<void>) => {
        setFailed(false);

        try {
            await action();
        } catch {
            setFailed(true);
        }
    };

    const rotate = () =>
        act(async () => {
            setSecret(
                await sendData<WooConnection>(
                    'POST',
                    path('/commerce/woocommerce/rotate-secret'),
                ),
            );
        });

    const disconnect = () =>
        act(async () => {
            await sendData('DELETE', path('/commerce/woocommerce'));
            setSecret(null);
            refresh();
        });

    return (
        <QueryState query={store}>
            {(data) => (
                <div className="flex flex-col gap-4" data-testid="store-connect">
                    <StatusLine
                        ok={data.connected}
                        label={
                            data.connected
                                ? t.onboarding.store.connected
                                : t.onboarding.store.notConnected
                        }
                    />

                    {data.connected && data.store && (
                        <dl className="grid grid-cols-[auto_1fr] gap-x-6 gap-y-1 text-sm">
                            <dt className="text-muted-foreground">
                                {t.onboarding.store.platform}
                            </dt>
                            <dd>
                                {t.onboarding.store.provider[data.store.provider] ??
                                    data.store.provider}
                            </dd>
                            {data.store.site_url && (
                                <>
                                    <dt className="text-muted-foreground">
                                        {t.onboarding.store.site}
                                    </dt>
                                    <dd dir="ltr" className="text-start break-all">
                                        {data.store.site_url}
                                    </dd>
                                </>
                            )}
                            {data.store.provider === 'woocommerce' && (
                                <>
                                    <dt className="text-muted-foreground">
                                        {t.onboarding.store.lastEvent}
                                    </dt>
                                    <dd>
                                        {data.store.last_webhook_at
                                            ? formatDateTime(
                                                  data.store.last_webhook_at,
                                                  locale,
                                              )
                                            : t.onboarding.store.noEventYet}
                                    </dd>
                                </>
                            )}
                        </dl>
                    )}

                    {secret && (
                        <WooSecretPanel
                            connection={secret}
                            onDone={() => setSecret(null)}
                        />
                    )}

                    {data.connected && data.store?.provider === 'woocommerce' && (
                        <div className="flex flex-wrap items-center gap-2">
                            <ConfirmButton
                                size="sm"
                                variant="outline"
                                message={t.onboarding.store.woo.rotateConfirm}
                                onConfirm={() => void rotate()}
                            >
                                {t.onboarding.store.woo.rotate}
                            </ConfirmButton>
                            <ConfirmButton
                                size="sm"
                                variant="ghost"
                                message={t.onboarding.store.woo.disconnectConfirm}
                                onConfirm={() => void disconnect()}
                            >
                                {t.onboarding.store.woo.disconnect}
                            </ConfirmButton>
                        </div>
                    )}

                    {failed && (
                        <p className="text-sm text-destructive" role="alert">
                            {t.common.error}
                        </p>
                    )}

                    {!data.connected && (
                        <PlatformChoice
                            onConnected={(connection) => {
                                setSecret(connection);
                                refresh();
                            }}
                        />
                    )}
                </div>
            )}
        </QueryState>
    );
}

function PlatformChoice({
    onConnected,
}: {
    onConnected: (connection: WooConnection) => void;
}) {
    const { t } = useI18n();
    const [platform, setPlatform] = useState<StoreProvider>('salla');
    const platforms: StoreProvider[] = ['salla', 'woocommerce'];

    return (
        <div className="flex flex-col gap-4">
            <div
                role="radiogroup"
                aria-label={t.onboarding.store.choose}
                className="flex flex-wrap gap-2"
            >
                {platforms.map((option) => (
                    <Button
                        key={option}
                        type="button"
                        role="radio"
                        aria-checked={platform === option}
                        size="sm"
                        variant={platform === option ? 'default' : 'outline'}
                        onClick={() => setPlatform(option)}
                    >
                        {t.onboarding.store.provider[option]}
                    </Button>
                ))}
            </div>

            {platform === 'salla' ? (
                <p className="text-sm text-muted-foreground">
                    {t.onboarding.store.howTo}
                </p>
            ) : (
                <WooConnectForm onConnected={onConnected} />
            )}
        </div>
    );
}

function WooConnectForm({
    onConnected,
}: {
    onConnected: (connection: WooConnection) => void;
}) {
    const { t } = useI18n();
    const { path } = useTeam();
    const { busy, errors, failed, run } = useSubmit();
    const [siteUrl, setSiteUrl] = useState('');
    const [consumerKey, setConsumerKey] = useState('');
    const [consumerSecret, setConsumerSecret] = useState('');
    const [conflict, setConflict] = useState<string | null>(null);
    const woo = t.onboarding.store.woo;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        setConflict(null);

        void run(async () => {
            try {
                const connection = await sendData<WooConnection>(
                    'POST',
                    path('/commerce/woocommerce'),
                    {
                        site_url: siteUrl.trim(),
                        consumer_key: consumerKey.trim(),
                        consumer_secret: consumerSecret.trim(),
                    },
                );
                // The keys are not needed in the browser any more.
                setConsumerKey('');
                setConsumerSecret('');
                onConnected(connection);
            } catch (error) {
                // 409: the store or workspace is taken; the server says which, in the page language.
                if (error instanceof ApiError && error.status === 409) {
                    setConflict(error.message);

                    return;
                }

                throw error;
            }
        });
    };

    return (
        <form
            onSubmit={submit}
            className="flex max-w-xl flex-col gap-4"
            data-testid="woo-connect-form"
        >
            <p className="text-sm text-muted-foreground">{woo.intro}</p>
            <Field
                id="woo_site_url"
                label={woo.siteUrl}
                help={woo.siteUrlHelp}
                type="url"
                inputMode="url"
                dir="ltr"
                autoComplete="url"
                placeholder="https://"
                required
                value={siteUrl}
                onChange={(event) => setSiteUrl(event.target.value)}
                error={errors.site_url}
            />
            <Field
                id="woo_consumer_key"
                label={woo.consumerKey}
                dir="ltr"
                autoComplete="off"
                spellCheck={false}
                placeholder="ck_…"
                required
                value={consumerKey}
                onChange={(event) => setConsumerKey(event.target.value)}
                error={errors.consumer_key}
            />
            <Field
                id="woo_consumer_secret"
                label={woo.consumerSecret}
                type="password"
                dir="ltr"
                autoComplete="off"
                spellCheck={false}
                placeholder="cs_…"
                required
                value={consumerSecret}
                onChange={(event) => setConsumerSecret(event.target.value)}
                error={errors.consumer_secret}
            />
            <FormErrors
                errors={errors}
                shown={['site_url', 'consumer_key', 'consumer_secret']}
            />
            {conflict && (
                <p className="text-sm text-destructive" role="alert">
                    {conflict}
                </p>
            )}
            {failed && (
                <p className="text-sm text-destructive" role="alert">
                    {t.common.error}
                </p>
            )}
            <Button type="submit" disabled={busy} className="self-start">
                {busy ? woo.connecting : woo.connect}
            </Button>
        </form>
    );
}

function WooSecretPanel({
    connection,
    onDone,
}: {
    connection: WooConnection;
    onDone: () => void;
}) {
    const { t } = useI18n();
    const woo = t.onboarding.store.woo;

    return (
        <div
            className="flex flex-col gap-4 rounded-xl border p-4"
            data-testid="woo-secret-panel"
        >
            <h3 className="text-sm font-semibold">{woo.panelTitle}</h3>
            <Alert>
                <TriangleAlert />
                <AlertTitle>{woo.secret}</AlertTitle>
                <AlertDescription>{woo.shownOnce}</AlertDescription>
            </Alert>
            <dl className="flex flex-col gap-3 text-sm">
                <CopyRow label={t.onboarding.store.storeId} value={connection.store_id} />
                <CopyRow label={woo.secret} value={connection.connector_secret} />
                <CopyRow label={woo.apiBase} value={connection.api_base} />
            </dl>
            <Button asChild variant="outline" size="sm" className="self-start">
                <a href={PLUGIN_ZIP} download>
                    <Download />
                    {woo.download}
                </a>
            </Button>
            <div className="flex flex-col gap-2">
                <h4 className="text-sm font-medium">{woo.stepsTitle}</h4>
                <ol className="flex list-decimal flex-col gap-1 ps-5 text-sm text-muted-foreground">
                    {woo.steps.map((step) => (
                        <li key={step}>{step}</li>
                    ))}
                </ol>
            </div>
            <Button size="sm" className="self-start" onClick={onDone}>
                {woo.done}
            </Button>
        </div>
    );
}

function CopyRow({ label, value }: { label: string; value: string }) {
    const { t } = useI18n();
    const [copied, setCopied] = useState(false);

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        } catch {
            setCopied(false);
        }
    };

    return (
        <div className="flex flex-col gap-1">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="flex items-center gap-2">
                <code
                    dir="ltr"
                    className="min-w-0 flex-1 break-all rounded-md bg-muted px-2 py-1 text-start font-mono text-xs"
                >
                    {value}
                </code>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={() => void copy()}
                    aria-label={`${copied ? t.onboarding.store.woo.copied : t.onboarding.store.woo.copy}: ${label}`}
                >
                    {copied ? <Check /> : <Copy />}
                    <span className={cn(copied && 'text-gain')}>
                        {copied
                            ? t.onboarding.store.woo.copied
                            : t.onboarding.store.woo.copy}
                    </span>
                </Button>
            </dd>
        </div>
    );
}
