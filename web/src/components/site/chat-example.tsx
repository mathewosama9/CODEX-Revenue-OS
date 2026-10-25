import { ArrowRightLeft, Check } from 'lucide-react';
import type { ReactNode } from 'react';

import { fill } from '@/i18n/site';
import { getSite } from '@/i18n/site/server';
import { formatSar } from '@/lib/money';
import { cn } from '@/lib/utils';

// Illustrative only: the price and stock stand in for what get_product would return.
// Prices are ink, never gold: the one gold number on a page is reserved for profit.
const EXAMPLE_PRICE_HALALAS = 19_900;
const EXAMPLE_STOCK = 4;

function Bubble({ from, label, children }: { from: 'customer' | 'assistant'; label: string; children: ReactNode }) {
    const mine = from === 'assistant';

    return (
        <div className={cn('flex max-w-[85%] flex-col gap-1', mine ? 'self-end items-end' : 'self-start items-start')}>
            <span className="text-xs text-muted-foreground">{label}</span>
            <div
                className={cn(
                    'rounded-2xl px-4 py-2.5 text-sm text-pretty shadow-card',
                    mine ? 'rounded-se-none bg-primary/15 text-foreground' : 'rounded-ss-none border bg-card',
                )}
            >
                {children}
            </div>
        </div>
    );
}

/** A WhatsApp-style conversation: a catalog answer, then a discount request handed to staff. */
export async function ChatExample({ className }: { className?: string }) {
    const { locale, s } = await getSite();
    const chat = s.aiAgent.chat;

    return (
        <figure
            className={cn('flex w-full max-w-md flex-col gap-4 rounded-xl rounded-ss-none border bg-sunken p-4 shadow-card md:p-5', className)}
            aria-label={s.common.example}
        >
            <figcaption className="w-fit rounded-full border border-border-strong px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                {s.common.example}
            </figcaption>

            <Bubble from="customer" label={chat.customer}>
                {chat.messages.ask}
            </Bubble>

            <Bubble from="assistant" label={chat.assistant}>
                <p>{fill(chat.messages.reply, { price: formatSar(EXAMPLE_PRICE_HALALAS, locale), stock: EXAMPLE_STOCK })}</p>
                <p className="mt-1 text-link underline underline-offset-2">
                    <bdi>{chat.messages.link}</bdi>
                </p>
                <Check aria-hidden className="ms-auto mt-1 block size-3.5 text-muted-foreground" />
            </Bubble>

            <Bubble from="customer" label={chat.customer}>
                {chat.messages.discount}
            </Bubble>

            <p role="note" className="flex items-center gap-2 self-center rounded-full border bg-card px-3 py-1.5 text-xs text-muted-foreground">
                <ArrowRightLeft aria-hidden className="size-3.5 shrink-0" />
                <span>
                    <span className="sr-only">{chat.system}: </span>
                    {chat.messages.handoff}
                </span>
            </p>
        </figure>
    );
}
