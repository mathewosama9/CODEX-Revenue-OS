'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Pause } from 'lucide-react';

import { ConfirmButton } from '@/components/confirm-button';
import { useI18n } from '@/i18n';
import { getData, sendData } from '@/lib/api';
import { useTeam } from '@/lib/session';

/**
 * Stops all automated sending now. Agents hold this too (US-E14-04): they see
 * the complaints first, in the inbox (review H7).
 */
export function KillSwitch() {
    const { t } = useI18n();
    const { path, slug, can } = useTeam();
    const client = useQueryClient();
    const allowed = can('ai:kill-switch');
    const key = [slug, 'recovery-status'];

    const status = useQuery({
        queryKey: key,
        queryFn: () => getData<{ enabled: boolean }>(path('/recovery/status')),
        enabled: allowed,
    });

    const pause = useMutation({
        mutationFn: () => sendData<unknown>('POST', path('/recovery/pause')),
        onSuccess: () => {
            client.setQueryData(key, { enabled: false });
            void client.invalidateQueries({ queryKey: [slug, 'recovery'] });
        },
    });

    if (!allowed || !status.data?.enabled) {
        return null;
    }

    return (
        <div className="flex items-center gap-2">
            <ConfirmButton variant="destructive" size="sm" message={t.recovery.pauseConfirm} onConfirm={() => pause.mutate()} disabled={pause.isPending}>
                <Pause />
                {t.recovery.pause}
            </ConfirmButton>
            {pause.isError && <span className="text-xs text-destructive">{t.common.error}</span>}
        </div>
    );
}
