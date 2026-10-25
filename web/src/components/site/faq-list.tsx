import { ChevronDown } from 'lucide-react';

/** Native <details>: keyboard and screen-reader friendly, no JavaScript. */
export function FaqList({ items }: { items: readonly { q: string; a: string }[] }) {
    return (
        <div className="flex flex-col gap-3" data-testid="faq-list">
            {items.map((item) => (
                <details
                    key={item.q}
                    className="group rounded-xl rounded-ss-none border bg-card shadow-card open:border-border-strong"
                >
                    <summary className="flex cursor-pointer list-none items-center justify-between gap-4 p-5 font-medium [&::-webkit-details-marker]:hidden">
                        <span className="text-pretty">{item.q}</span>
                        <ChevronDown
                            aria-hidden
                            className="size-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180 motion-reduce:transition-none"
                        />
                    </summary>
                    <p className="px-5 pb-5 text-pretty text-muted-foreground">{item.a}</p>
                </details>
            ))}
        </div>
    );
}
