'use client';

import { useState } from 'react';

import { ApiError } from '@/lib/api';

/** Submit state + Laravel validation errors for a small form. */
export function useSubmit() {
    const [busy, setBusy] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [failed, setFailed] = useState(false);

    async function run(action: () => Promise<void>) {
        setBusy(true);
        setErrors({});
        setFailed(false);

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
            }
        } finally {
            setBusy(false);
        }
    }

    return { busy, errors, failed, run };
}
