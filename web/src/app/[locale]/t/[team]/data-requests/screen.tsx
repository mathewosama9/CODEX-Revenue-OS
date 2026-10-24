'use client';

import { useState } from 'react';

import { ConfirmButton } from '@/components/confirm-button';
import { Field } from '@/components/field';
import { PageHeader } from '@/components/page-header';
import { Section } from '@/components/section';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { sendData } from '@/lib/api';
import { saveBlob } from '@/lib/download';
import { useTeam } from '@/lib/session';
import { useSubmit } from '@/lib/use-form';

export default function DataRequestsPage() {
    const { t } = useI18n();
    const { path } = useTeam();
    const { busy, errors, failed, run } = useSubmit();
    const [phone, setPhone] = useState('');
    const [done, setDone] = useState<string | null>(null);

    const send = (type: 'export' | 'delete') => {
        setDone(null);

        void run(async () => {
            const result = await sendData<unknown>('POST', path('/data-requests'), { phone, type });

            if (type === 'export') {
                // Straight to a file; the export is never kept in the app.
                saveBlob(new Blob([JSON.stringify(result, null, 2)], { type: 'application/json' }), 'customer-data.json');
                setDone(t.dataRequests.exported);
            } else {
                setDone(t.dataRequests.deleted);
            }

            setPhone('');
        });
    };

    return (
        <>
            <PageHeader title={t.dataRequests.title} subtitle={t.dataRequests.subtitle} />
            <Section title={t.dataRequests.phone}>
                <form
                    className="flex max-w-sm flex-col gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        send('export');
                    }}
                >
                    <Field
                        id="phone"
                        type="tel"
                        dir="ltr"
                        autoComplete="off"
                        className="font-numeric"
                        label={t.dataRequests.phone}
                        help={t.dataRequests.phoneHelp}
                        value={phone}
                        onChange={(event) => setPhone(event.target.value)}
                        error={errors.phone}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" disabled={busy || phone === ''}>
                            {t.dataRequests.export}
                        </Button>
                        <ConfirmButton
                            type="button"
                            variant="destructive"
                            disabled={busy || phone === ''}
                            message={t.dataRequests.deleteConfirm}
                            onConfirm={() => send('delete')}
                        >
                            {t.dataRequests.delete}
                        </ConfirmButton>
                    </div>
                    {failed && <p className="text-sm text-destructive">{t.common.error}</p>}
                    {done && <p className="text-sm text-gain" role="status">{done}</p>}
                </form>
            </Section>
        </>
    );
}
