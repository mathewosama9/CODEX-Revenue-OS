'use client';

import { useQuery } from '@tanstack/react-query';
import { Download } from 'lucide-react';
import { useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { QueryState } from '@/components/query-state';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n';
import { getData, request } from '@/lib/api';
import { saveBlob } from '@/lib/download';
import { useTeam } from '@/lib/session';
import { riyadhToday } from '@/lib/time';
import type { Usage } from '@/lib/types';

// The merchant's month (Asia/Riyadh), like UsageMeter on the server (review L3).
const thisMonth = () => riyadhToday().slice(0, 7);
const validMonth = (month: string) => /^\d{4}-(0[1-9]|1[0-2])$/.test(month);
const count = new Intl.NumberFormat('en-US');

export default function UsagePage() {
    const { t } = useI18n();
    const { path, slug } = useTeam();
    const [month, setMonth] = useState(thisMonth);
    const [downloading, setDownloading] = useState(false);
    const [downloadFailed, setDownloadFailed] = useState(false);

    const usage = useQuery({
        queryKey: [slug, 'usage', month],
        queryFn: () => getData<Usage>(path(`/billing/usage?month=${month}`)),
        enabled: validMonth(month),
    });

    const download = async () => {
        setDownloading(true);
        setDownloadFailed(false);

        try {
            const response = await request(
                'GET',
                `/api/v1${path(`/billing/usage?month=${month}&format=csv`)}`,
            );
            saveBlob(await response.blob(), `usage-${month}.csv`);
        } catch {
            setDownloadFailed(true);
        } finally {
            setDownloading(false);
        }
    };

    // "messages_delivered:marketing" → "Messages delivered · marketing"
    const label = (metric: string) => {
        const [base, detail] = metric.split(':');

        return detail
            ? `${t.usage.metric[base] ?? base} · ${detail}`
            : (t.usage.metric[base] ?? base);
    };

    return (
        <>
            <PageHeader
                title={t.usage.title}
                subtitle={t.usage.subtitle}
                actions={
                    <Button
                        variant="outline"
                        onClick={download}
                        disabled={downloading || !validMonth(month)}
                    >
                        <Download />
                        {t.usage.download}
                    </Button>
                }
            />
            <div className="flex flex-col gap-1.5">
                <Label htmlFor="month">{t.usage.month}</Label>
                <Input
                    id="month"
                    type="month"
                    dir="ltr"
                    className="w-44 font-numeric"
                    value={month}
                    onChange={(event) => setMonth(event.target.value)}
                />
            </div>
            {downloadFailed && (
                <p className="text-sm text-destructive">{t.common.error}</p>
            )}
            {/* Browsers without a month picker take free text: say what's expected instead of spinning. */}
            {!validMonth(month) ? (
                <p className="text-sm text-muted-foreground">
                    {t.usage.monthFormat}
                </p>
            ) : (
                <QueryState query={usage}>
                    {(data) =>
                        Object.keys(data.usage).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t.usage.empty}
                            </p>
                        ) : (
                            <table
                                className="w-full overflow-hidden rounded-2xl rounded-ss-none border bg-card text-sm shadow-card"
                                data-testid="usage-table"
                            >
                                <tbody className="divide-y">
                                    {Object.entries(data.usage).map(
                                        ([metric, total]) => (
                                            <tr key={metric}>
                                                <th
                                                    scope="row"
                                                    className="px-5 py-3 text-start font-normal"
                                                >
                                                    {label(metric)}
                                                </th>
                                                <td className="px-5 py-3 text-end font-numeric tabular-nums">
                                                    {count.format(total)}
                                                </td>
                                            </tr>
                                        ),
                                    )}
                                </tbody>
                            </table>
                        )
                    }
                </QueryState>
            )}
        </>
    );
}
