import Screen from './screen';

// Merchant data loads in the browser after sign-in, so this route is allowed
// to block (see the workspace layout).
export const instant = false;

export default function Page() {
    return <Screen />;
}
