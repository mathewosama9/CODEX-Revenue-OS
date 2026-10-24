'use client';

import { useI18n } from '@/i18n';
import { useMe } from '@/lib/session';

/** "You", "Teammate" or "Unassigned". The API has no member list yet. */
export function Assignee({ userId }: { userId: number | null }) {
    const { t } = useI18n();
    const { data: me } = useMe();

    if (userId === null) {
        return <>{t.inbox.unassigned}</>;
    }

    return <>{userId === me?.id ? t.inbox.you : t.inbox.teammate}</>;
}
