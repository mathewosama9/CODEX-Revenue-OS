'use client';

import { StatusLine } from '@/components/status-dot';
import { useI18n } from '@/i18n';
import type { Checklist } from '@/lib/types';

export function GoLiveChecklist({ items }: { items: Checklist }) {
    const { t } = useI18n();

    return (
        <ul className="flex flex-col gap-2" data-testid="go-live-checklist">
            {Object.entries(items).map(([key, ok]) => (
                <li key={key}>
                    <StatusLine ok={ok} label={t.onboarding.checklist.items[key] ?? key} />
                </li>
            ))}
        </ul>
    );
}

export const checklistPasses = (items: Checklist) => Object.values(items).every(Boolean);
