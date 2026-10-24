import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

// Arabic is the default; a visitor who picked English keeps it (locale cookie).
export function proxy(request: NextRequest) {
    const locale = request.cookies.get('locale')?.value === 'en' ? 'en' : 'ar';

    return NextResponse.redirect(new URL(`/${locale}`, request.url));
}

export const config = {
    matcher: '/',
};
