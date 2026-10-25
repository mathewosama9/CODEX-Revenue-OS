'use client';

import { useQuery } from '@tanstack/react-query';

import { useI18n } from '@/i18n';
import { getData } from '@/lib/api';
import { useTeam } from '@/lib/session';
import type { Members } from '@/lib/types';

/** Workspace members, for names and assignment in the inbox. */
export function useMembers() {
    const { path, slug } = useTeam();

    return useQuery({
        queryKey: [slug, 'members'],
        queryFn: () => getData<Members>(path('/members')),
        staleTime: 5 * 60_000,
    });
}

/** "You", the teammate's name, or "Unassigned". */
export function Assignee({ userId }: { userId: number | null }) {
    const { t } = useI18n();
    const { me } = useTeam();
    const { data } = useMembers();

    if (userId === null) {
        return <>{t.inbox.unassigned}</>;
    }

    if (userId === me?.id) {
        return <>{t.inbox.you}</>;
    }

    return <>{data?.members.find((member) => member.id === userId)?.name ?? t.inbox.teammate}</>;
}
