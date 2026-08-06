const SESSION_EXPIRED_MESSAGE = 'Session expired. Reload page and try again.';

function xsrfCookieToken(): string | null {
    const cookie = document.cookie
        .split(';')
        .map((part) => part.trim())
        .find((part) => part.startsWith('XSRF-TOKEN='));

    if (!cookie) return null;

    try {
        return decodeURIComponent(cookie.slice('XSRF-TOKEN='.length));
    } catch {
        return cookie.slice('XSRF-TOKEN='.length);
    }
}

export function cashflowCsrfHeaders(): Record<string, string> | null {
    const cookieToken = xsrfCookieToken();
    if (cookieToken) return { 'X-XSRF-TOKEN': cookieToken };

    const metaToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

    return metaToken ? { 'X-CSRF-TOKEN': metaToken } : null;
}

export async function cashflowResponsePayload<T>(response: Response, fallback: string): Promise<T> {
    if (response.status === 419 || response.redirected) {
        throw new Error(SESSION_EXPIRED_MESSAGE);
    }

    const contentType = response.headers?.get?.('content-type') ?? '';
    if (contentType !== '' && !contentType.includes('application/json')) {
        throw new Error(fallback);
    }

    let payload: T & { message?: unknown };
    try {
        payload = await response.json() as T & { message?: unknown };
    } catch {
        throw new Error(fallback);
    }

    if (!response.ok) {
        throw new Error(typeof payload.message === 'string' ? payload.message : fallback);
    }

    return payload;
}
