// Laravel is the backend (ADR-002). Sanctum SPA auth: the session lives in an
// HttpOnly cookie set by Laravel, and every write carries the XSRF token.
// Nothing about the user or their customers is stored in the browser.
export const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

export class ApiError extends Error {
    constructor(
        public readonly status: number,
        message: string,
        /** Laravel validation errors, keyed by field. */
        public readonly errors: Record<string, string[]> = {},
    ) {
        super(message);
    }

    firstError(field?: string): string | undefined {
        if (field) {
            return this.errors[field]?.[0];
        }

        return Object.values(this.errors)[0]?.[0] ?? this.message;
    }
}

function xsrfToken(): string | undefined {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : undefined;
}

let csrfReady: Promise<void> | null = null;

/** Sets the XSRF-TOKEN cookie once per page load; Laravel rotates it on login. */
export function ensureCsrf(force = false): Promise<void> {
    if (force || !csrfReady || !xsrfToken()) {
        csrfReady = fetch(`${API_URL}/sanctum/csrf-cookie`, {
            credentials: 'include',
        }).then(() => undefined);
    }

    return csrfReady;
}

type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

/** Fortify's guest and auth routes: a 401 there is an answer, not an expired session. */
const AUTH_PATHS = ['/login', '/logout', '/register', '/forgot-password', '/reset-password', '/two-factor-challenge', '/api/v1/me'];

/** Fired when the session is gone mid-use; the app sends the user to sign in (review B8). */
export const SESSION_EXPIRED_EVENT = 'codex:session-expired';

/**
 * Status 0 means Laravel answered with a redirect (we never follow one): on a
 * guest route that is "already signed in", on the 2FA route "the pending
 * sign-in expired" (review H9).
 */
export const REDIRECTED = 0;

async function send(method: Method, path: string, body?: unknown): Promise<Response> {
    if (method !== 'GET') {
        await ensureCsrf();
    }

    const headers: Record<string, string> = {
        Accept: 'application/json',
        // Laravel answers validation errors in the language of the page.
        'Accept-Language': document.documentElement.lang || 'ar',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const token = xsrfToken();

    if (token) {
        headers['X-XSRF-TOKEN'] = token;
    }

    // FormData (file uploads) sets its own multipart boundary.
    const isForm = body instanceof FormData;

    if (body !== undefined && !isForm) {
        headers['Content-Type'] = 'application/json';
    }

    return fetch(`${API_URL}${path}`, {
        method,
        headers,
        credentials: 'include',
        // A redirect from the API would land on a page CORS doesn't cover.
        redirect: 'manual',
        body: body === undefined ? undefined : isForm ? body : JSON.stringify(body),
    });
}

export async function request(
    method: Method,
    path: string,
    body?: unknown,
): Promise<Response> {
    let response = await send(method, path, body);

    // 419: the XSRF token went stale (Laravel rotated it). Fetch a fresh one once.
    if (response.status === 419) {
        await ensureCsrf(true);
        response = await send(method, path, body);
    }

    if (response.type === 'opaqueredirect') {
        throw new ApiError(REDIRECTED, 'redirected');
    }

    if (!response.ok) {
        const payload = await response.json().catch(() => ({}));

        if ((response.status === 401 || response.status === 419) && !AUTH_PATHS.includes(path.split('?')[0])) {
            window.dispatchEvent(new Event(SESSION_EXPIRED_EVENT));
        }

        throw new ApiError(
            response.status,
            typeof payload.message === 'string' ? payload.message : response.statusText,
            payload.errors ?? {},
        );
    }

    return response;
}

export async function api<T>(method: Method, path: string, body?: unknown): Promise<T> {
    const response = await request(method, path, body);

    if (response.status === 204) {
        return undefined as T;
    }

    const text = await response.text();

    return (text ? JSON.parse(text) : undefined) as T;
}

/** GET /api/v1/... and unwrap Laravel's `{ data }` envelope. */
export async function getData<T>(path: string): Promise<T> {
    return (await api<{ data: T }>('GET', `/api/v1${path}`)).data;
}

/** A 204 (e.g. a DELETE) has no body: resolves to undefined. */
export async function sendData<T>(method: Method, path: string, body?: unknown): Promise<T> {
    return (await api<{ data: T } | undefined>(method, `/api/v1${path}`, body))?.data as T;
}
