'use client';

import { useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useI18n } from '@/i18n';

/** A button that asks before doing something hard to undo. */
export function ConfirmButton({
    message,
    onConfirm,
    children,
    ...props
}: Omit<ComponentProps<typeof Button>, 'onClick'> & {
    message: string;
    onConfirm: () => void;
    children: ReactNode;
}) {
    const { t } = useI18n();
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button {...props}>{children}</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{children}</DialogTitle>
                <DialogDescription>{message}</DialogDescription>
                <DialogFooter className="gap-2">
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        {t.common.cancel}
                    </Button>
                    <Button
                        variant={props.variant}
                        data-testid="confirm"
                        onClick={() => {
                            setOpen(false);
                            onConfirm();
                        }}
                    >
                        {children}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
