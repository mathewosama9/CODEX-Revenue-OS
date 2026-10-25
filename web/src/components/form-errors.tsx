/**
 * Validation errors for fields this form doesn't render (a category row, a
 * setting changed elsewhere). Without this a 422 looked like nothing happened
 * (review H16).
 */
export function FormErrors({ errors, shown }: { errors: Record<string, string>; shown: string[] }) {
    const others = Object.entries(errors).filter(([field]) => !shown.includes(field));

    if (others.length === 0) {
        return null;
    }

    return (
        <ul className="flex flex-col gap-1 text-sm text-destructive" role="alert">
            {others.map(([field, message]) => (
                <li key={field}>{message}</li>
            ))}
        </ul>
    );
}
