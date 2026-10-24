'use client';

import type { UseQueryResult } from '@tanstack/react-query';
import type { ReactNode } from 'react';

import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/i18n';
import { ApiError } from '@/lib/api';

/** Loading, error and forbidden states for one query; renders children with the data. */
export function QueryState<T>({
    query,
    children,
}: {
    query: UseQueryResult<T>;
    children: (data: T) => ReactNode;
}) {
    const { t } = useI18n();

    if (query.isPending) {
        return (
            <div className="flex items-center gap-2 py-8 text-sm text-muted-foreground">
                <Spinner /> {t.common.loading}
            </div>
        );
    }

    if (query.isError) {
        return (
            <Alert variant="destructive">
                <AlertDescription className="flex flex-wrap items-center gap-3">
                    {query.error instanceof ApiError && query.error.status === 403
                        ? t.common.forbidden
                        : t.common.error}
                    <Button variant="outline" size="sm" onClick={() => query.refetch()}>
                        {t.common.retry}
                    </Button>
                </AlertDescription>
            </Alert>
        );
    }

    return <>{children(query.data)}</>;
}
