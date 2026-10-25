'use client';

import { useState } from 'react';

import { ApiError } from '@/lib/api';

/** Submit state + Laravel validation errors for a small form. */
export function useSubmit() {
    const [busy, setBusy] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [failed, setFailed] = useState(false);
    /** HTTP status of the last failure (429 = slow down), when it wasn't a validation error. */
    const [failedStatus, setFailedStatus] = useState<number | null>(null);

    async function run(action: () => Promise<void>) {
        setBusy(true);
        setErrors({});
        setFailed(false);
        setFailedStatus(null);

        try {
            await action();
        } catch (error) {
            if (error instanceof ApiError && error.status === 422) {
                setErrors(
                    Object.fromEntries(
                        Object.entries(error.errors).map(([field, messages]) => [
                            field,
                            messages[0],
                        ]),
                    ),
                );
            } else {
                setFailed(true);
                setFailedStatus(error instanceof ApiError ? error.status : null);
            }
        } finally {
            setBusy(false);
        }
    }

    return { busy, errors, failed, failedStatus, run };
}
