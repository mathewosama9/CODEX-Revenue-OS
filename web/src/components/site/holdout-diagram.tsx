// 100 customers, 10 of them in the comparison group. Colours come from currentColor,
// so both themes work; the comparison dots are hollow, so it never relies on colour alone.
const SIZE = 10;
const GAP = 24;

/** One comparison customer per row, spread out: the split is per customer, not a block. */
const isControl = (row: number, column: number) => column === (row * 7 + 3) % SIZE;

export function HoldoutDiagram({ label, treated, control }: { label: string; treated: string; control: string }) {
    const dots = Array.from({ length: SIZE * SIZE }, (_, index) => {
        const row = Math.floor(index / SIZE);
        const column = index % SIZE;

        return { row, column, control: isControl(row, column) };
    });

    return (
        <figure className="flex flex-col gap-5 rounded-xl rounded-ss-none border bg-card p-6 shadow-card">
            <svg
                viewBox={`0 0 ${SIZE * GAP} ${SIZE * GAP}`}
                role="img"
                aria-label={label}
                className="mx-auto w-full max-w-72"
            >
                {dots.map((dot) => (
                    <circle
                        key={`${dot.row}-${dot.column}`}
                        cx={dot.column * GAP + GAP / 2}
                        cy={dot.row * GAP + GAP / 2}
                        r={dot.control ? 6.5 : 7.5}
                        className={dot.control ? 'text-muted-foreground' : 'text-link'}
                        fill={dot.control ? 'none' : 'currentColor'}
                        stroke={dot.control ? 'currentColor' : 'none'}
                        strokeWidth={2}
                    />
                ))}
            </svg>
            <figcaption className="flex flex-col gap-2 text-sm">
                <span className="flex items-center gap-2">
                    <span aria-hidden className="size-3.5 shrink-0 rounded-full bg-link" />
                    {treated}
                </span>
                <span className="flex items-center gap-2 text-muted-foreground">
                    <span aria-hidden className="size-3.5 shrink-0 rounded-full border-2 border-muted-foreground" />
                    {control}
                </span>
            </figcaption>
        </figure>
    );
}
