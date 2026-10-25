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

export async function request(
    method: Method,
    path: string,
    body?: unknown,
): Promise<Response> {
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

    const response = await fetch(`${API_URL}${path}`, {
        method,
        headers,
        credentials: 'include',
        body: body === undefined ? undefined : isForm ? body : JSON.stringify(body),
    });

    if (!response.ok) {
        const payload = await response.json().catch(() => ({}));

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

export async function sendData<T>(method: Method, path: string, body?: unknown): Promise<T> {
    return (await api<{ data: T }>(method, `/api/v1${path}`, body)).data;
}
