import { Check, X } from 'lucide-react';

/** "The usual way" against CODEX, as a real table; icons always come with text. No competitor names. */
export function ComparisonTable({
    caption,
    topic,
    usual,
    codex,
    rows,
}: {
    caption: string;
    topic: string;
    usual: string;
    codex: string;
    rows: { topic: string; usual: string; codex: string }[];
}) {
    return (
        // Scrolls inside its card if a phone is ever too narrow; the page itself never does.
        <div className="overflow-x-auto rounded-xl rounded-ss-none border bg-card shadow-card">
            <table className="w-full min-w-[20rem] border-collapse text-start text-sm">
                <caption className="sr-only">{caption}</caption>
                <thead>
                    <tr className="border-b bg-sunken">
                        <th scope="col" className="p-3 text-start font-medium text-muted-foreground md:p-4">{topic}</th>
                        <th scope="col" className="p-3 text-start font-medium text-muted-foreground md:p-4">{usual}</th>
                        <th scope="col" className="p-3 text-start font-semibold md:p-4">
                            <span className="text-link">{codex}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr key={row.topic} className="border-b align-top last:border-b-0">
                            <th scope="row" className="p-3 text-start font-medium md:p-4">{row.topic}</th>
                            <td className="p-3 text-muted-foreground md:p-4">
                                <span className="flex gap-2">
                                    <X aria-hidden className="mt-0.5 size-4 shrink-0 text-loss" />
                                    {row.usual}
                                </span>
                            </td>
                            <td className="p-3 md:p-4">
                                <span className="flex gap-2">
                                    <Check aria-hidden className="mt-0.5 size-4 shrink-0 text-gain" />
                                    {row.codex}
                                </span>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
