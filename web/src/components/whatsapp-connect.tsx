'use client';

import { useEffect, useRef, useState } from 'react';

import { Field } from '@/components/field';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { useSubmit } from '@/lib/use-form';

const APP_ID = process.env.NEXT_PUBLIC_META_APP_ID;
const CONFIG_ID = process.env.NEXT_PUBLIC_META_CONFIG_ID;
const GRAPH_VERSION = 'v24.0';

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

function loadSdk(): Promise<FacebookSdk> {
    return new Promise((resolve) => {
        if (window.FB) {
            resolve(window.FB);

            return;
        }

        window.fbAsyncInit = () => {
            window.FB!.init({ appId: APP_ID, autoLogAppEvents: true, xfbml: false, version: GRAPH_VERSION });
            resolve(window.FB!);
        };

        const script = document.createElement('script');
        script.src = 'https://connect.facebook.net/en_US/sdk.js';
        script.async = true;
        script.crossOrigin = 'anonymous';
        document.body.appendChild(script);
    });
}

export type SignupResult = { code: string; waba_id: string; phone_number_id: string; pin: string };

/**
 * WhatsApp Embedded Signup v4 (official Meta flow, Rule 8). The browser gets a
 * one-time code plus the WABA and number ids; Laravel finishes the exchange.
 */
export function WhatsAppConnect({ onComplete }: { onComplete: (result: SignupResult) => Promise<void> }) {
    const { t } = useI18n();
    const { busy, errors, failed, run } = useSubmit();
    const session = useRef<{ waba_id?: string; phone_number_id?: string }>({});
    const [code, setCode] = useState<string | null>(null);
    const [pin, setPin] = useState('');
    const [cancelled, setCancelled] = useState(false);

    useEffect(() => {
        const listener = (event: MessageEvent) => {
            if (!/^https:\/\/([a-z0-9-]+\.)?facebook\.com$/.test(event.origin)) {
                return;
            }

            try {
                const payload = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;

                if (payload?.type === 'WA_EMBEDDED_SIGNUP' && payload.event?.startsWith('FINISH')) {
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

    if (!APP_ID || !CONFIG_ID) {
        return (
            <Alert>
                <AlertDescription>{t.onboarding.whatsapp.notConfigured}</AlertDescription>
            </Alert>
        );
    }

    const start = async () => {
        setCancelled(false);
        const fb = await loadSdk();

        fb.login(
            (response) => {
                if (response.authResponse?.code) {
                    setCode(response.authResponse.code);
                } else {
                    setCancelled(true);
                }
            },
            {
                config_id: CONFIG_ID,
                response_type: 'code',
                override_default_response_type: true,
                extras: { setup: {}, featureType: '', sessionInfoVersion: '3' },
            },
        );
    };

    if (code === null) {
        return (
            <div className="flex flex-col gap-3">
                <Button onClick={start} className="self-start">
                    {t.onboarding.whatsapp.connect}
                </Button>
                {cancelled && <p className="text-sm text-destructive">{t.onboarding.whatsapp.cancelled}</p>}
            </div>
        );
    }

    const finish = (event: React.FormEvent) => {
        event.preventDefault();

        void run(async () => {
            const { waba_id, phone_number_id } = session.current;

            if (!waba_id || !phone_number_id) {
                setCode(null);
                setCancelled(true);

                return;
            }

            await onComplete({ code, waba_id, phone_number_id, pin });
        });
    };

    return (
        <form onSubmit={finish} className="flex max-w-xs flex-col gap-3">
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
                error={errors.pin ?? errors.code}
            />
            {failed && <p className="text-sm text-destructive">{t.common.error}</p>}
            <Button type="submit" disabled={busy || pin.length !== 6}>
                {t.onboarding.whatsapp.finish}
            </Button>
        </form>
    );
}
