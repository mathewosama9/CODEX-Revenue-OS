'use client';

import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useParams, useRouter } from 'next/navigation';
import { useCallback } from 'react';

import { useI18n } from '@/i18n';
import { api, ApiError, ensureCsrf, getData } from '@/lib/api';

/** Mirrors App\Enums\TeamPermission. */
export type Permission =
    | 'integrations:manage'
    | 'recovery:manage'
    | 'discounts:manage'
    | 'margins:manage'
    | 'reports:view'
    | 'billing:manage'
    | 'data-requests:manage'
    | 'inbox:use'
    | 'ai:kill-switch'
    | (string & {});

export type TeamMembership = {
    slug: string;
    name: string;
    role: 'owner' | 'agent';
    permissions: Permission[];
};

export type Me = {
    id: number;
    name: string;
    email: string;
    current_team: string | null;
    teams: TeamMembership[];
};

export function useMe() {
    return useQuery({
        queryKey: ['me'],
        queryFn: () => getData<Me>('/me'),
        staleTime: 5 * 60_000,
    });
}

export function isUnauthenticated(error: unknown): boolean {
    return error instanceof ApiError && error.status === 401;
}

/** `/ar/...` or `/en/...` for the current locale. */
export function useHref() {
    const { locale } = useI18n();

    return useCallback((path: string) => `/${locale}${path}`, [locale]);
}

/** The workspace in the URL, and what the signed-in user may do in it. */
export function useTeam() {
    const params = useParams<{ team: string }>();
    const { data: me } = useMe();
    const team = me?.teams.find((candidate) => candidate.slug === params.team);

    return {
        slug: params.team,
        team,
        me,
        can: (permission: Permission) =>
            team?.permissions.includes(permission) ?? false,
        /** Tenant API path: /teams/{slug}/... */
        path: (suffix: string) => `/teams/${params.team}${suffix}`,
    };
}

export function useSignOut() {
    const client = useQueryClient();
    const router = useRouter();
    const href = useHref();

    return async () => {
        await api('POST', '/logout').catch(() => undefined);
        client.clear();
        router.replace(href('/login'));
    };
}

/** After login Laravel regenerates the session, so fetch a fresh XSRF token. */
export async function afterSignIn(client: ReturnType<typeof useQueryClient>) {
    await ensureCsrf(true);
    await client.invalidateQueries({ queryKey: ['me'] });
}
