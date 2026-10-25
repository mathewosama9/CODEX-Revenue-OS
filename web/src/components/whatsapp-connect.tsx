'use client';

import { useEffect, useRef, useState } from 'react';

import { Field } from '@/components/field';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { ApiError } from '@/lib/api';

const APP_ID = process.env.NEXT_PUBLIC_META_APP_ID;
const CONFIG_ID = process.env.NEXT_PUBLIC_META_CONFIG_ID;
const GRAPH_VERSION = 'v24.0';
const SDK_TIMEOUT_MS = 15_000;
/** How long to wait for Meta's FINISH message after the login callback. */
const SESSION_WAIT_MS = 5_000;

type FacebookSdk = {
    init: (options: Record<string, unknown>) => void;
    login: (
        callback: (response: { authResponse?: { code?: string } }) => void,
        options: Record<string, unknown>,
    ) => void;
};

declare global {
    interface Window {
        FB?: FacebookSdk;
        fbAsyncInit?: () => void;
    }
}

let sdkPromise: Promise<FacebookSdk> | null = null;

/** Loads Meta's SDK once; rejects if it is blocked or never arrives. */
function loadSdk(): Promise<FacebookSdk> {
    if (window.FB) {
        return Promise.resolve(window.FB);
    }

    sdkPromise ??= new Promise<FacebookSdk>((resolve, reject) => {
        const timer = window.setTimeout(() => reject(new Error('sdk_timeout')), SDK_TIMEOUT_MS);

        window.fbAsyncInit = () => {
            window.clearTimeout(timer);
            window.FB!.init({ appId: APP_ID, autoLogAppEvents: true, xfbml: false, version: GRAPH_VERSION });
            resolve(window.FB!);
        };

        const script = document.createElement('script');
        script.src = 'https://connect.facebook.net/en_US/sdk.js';
        script.async = true;
        script.crossOrigin = 'anonymous';
        script.onerror = () => {
            window.clearTimeout(timer);
            reject(new Error('sdk_blocked'));
        };
        document.body.appendChild(script);
    }).catch((error) => {
        sdkPromise = null; // Allow a later retry.

        throw error;
    });

    return sdkPromise;
}

export type SignupResult = { code: string; waba_id: string; phone_number_id: string; pin: string };

type Session = { waba_id?: string; phone_number_id?: string };

/**
 * WhatsApp Embedded Signup v4 (official Meta flow, Rule 8). Review B9:
 * - the PIN is asked first, so the short-lived code is sent the moment Meta returns it;
 * - the SDK loads ahead, so FB.login opens inside the click (no popup blocking);
 * - every failure resets the flow and shows the reason from the server.
 */
export function WhatsAppConnect({ onComplete }: { onComplete: (result: SignupResult) => Promise<void> }) {
    const { t } = useI18n();
    const session = useRef<Session>({});
    const [sdk, setSdk] = useState<'loading' | 'ready' | 'failed'>('loading');
    const [pin, setPin] = useState('');
    const [busy, setBusy] = useState(false);
    const [problem, setProblem] = useState<string | null>(null);
    const configured = Boolean(APP_ID && CONFIG_ID);

    useEffect(() => {
        if (!configured) {
            return;
        }

        loadSdk().then(
            () => setSdk('ready'),
            () => setSdk('failed'),
        );
    }, [configured]);

    useEffect(() => {
        const listener = (event: MessageEvent) => {
            if (!/^https:\/\/([a-z0-9-]+\.)?facebook\.com$/.test(event.origin)) {
                return;
            }

            try {
                const payload = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;

                if (payload?.type === 'WA_EMBEDDED_SIGNUP' && typeof payload.event === 'string' && payload.event.startsWith('FINISH')) {
                    session.current = {
                        waba_id: payload.data?.waba_id,
                        phone_number_id: payload.data?.phone_number_id,
                    };
                }
            } catch {
                // Other Facebook messages are not JSON; ignore them.
            }
        };

        window.addEventListener('message', listener);

        return () => window.removeEventListener('message', listener);
    }, []);

    if (!configured) {
        return (
            <Alert>
                <AlertDescription>{t.onboarding.whatsapp.notConfigured}</AlertDescription>
            </Alert>
        );
    }

    /** Meta usually posts FINISH before the login callback, but not always. */
    const waitForSession = async (): Promise<Session> => {
        const deadline = Date.now() + SESSION_WAIT_MS;

        while (Date.now() < deadline && !session.current.phone_number_id) {
            await new Promise((resolve) => window.setTimeout(resolve, 100));
        }

        return session.current;
    };

    const finish = async (code: string) => {
        const { waba_id, phone_number_id } = await waitForSession();

        if (!waba_id || !phone_number_id) {
            // FINISH_ONLY_WABA: an account without a number was chosen.
            setProblem(t.onboarding.whatsapp.noNumber);

            return;
        }

        try {
            await onComplete({ code, waba_id, phone_number_id, pin });
        } catch (error) {
            setProblem(error instanceof ApiError ? (error.firstError('signup') ?? error.firstError('pin') ?? t.common.error) : t.common.error);
        }
    };

    // Synchronous inside the click, so the browser treats Meta's window as user-initiated.
    const start = () => {
        setProblem(null);
        session.current = {};
        setBusy(true);

        window.FB!.login(
            (response) => {
                const code = response.authResponse?.code;

                if (!code) {
                    setProblem(t.onboarding.whatsapp.cancelled);
                    setBusy(false);

                    return;
                }

                void finish(code).finally(() => setBusy(false));
            },
            {
                config_id: CONFIG_ID,
                response_type: 'code',
                override_default_response_type: true,
                extras: { setup: {}, featureType: '', sessionInfoVersion: '3' },
            },
        );
    };

    return (
        <div className="flex max-w-xs flex-col gap-3" data-testid="whatsapp-connect">
            <Field
                id="pin"
                dir="ltr"
                inputMode="numeric"
                maxLength={6}
                className="font-numeric tracking-widest"
                label={t.onboarding.whatsapp.pinTitle}
                help={t.onboarding.whatsapp.pinHelp}
                value={pin}
                onChange={(event) => setPin(event.target.value.replace(/\D/g, ''))}
            />
            <Button onClick={start} disabled={sdk !== 'ready' || busy || pin.length !== 6} className="self-start">
                {t.onboarding.whatsapp.connect}
            </Button>
            {sdk === 'failed' && <p className="text-sm text-destructive">{t.onboarding.whatsapp.sdkBlocked}</p>}
            {problem && (
                <p className="text-sm text-destructive" role="alert">
                    {problem}
                </p>
            )}
        </div>
    );
}
