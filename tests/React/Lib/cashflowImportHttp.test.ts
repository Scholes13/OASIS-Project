import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { cashflowCsrfHeaders, cashflowResponsePayload } from '@/lib/cashflowImportHttp';

describe('cashflow import HTTP helpers', () => {
    beforeEach(() => {
        document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        document.querySelector('meta[name="csrf-token"]')?.remove();
    });

    afterEach(() => {
        document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        document.querySelector('meta[name="csrf-token"]')?.remove();
    });

    it('prefers the fresh XSRF cookie over a stale meta token', () => {
        const meta = document.createElement('meta');
        meta.name = 'csrf-token';
        meta.content = 'stale-meta-token';
        document.head.appendChild(meta);
        document.cookie = `XSRF-TOKEN=${encodeURIComponent('fresh cookie token')}; path=/`;

        expect(cashflowCsrfHeaders()).toEqual({ 'X-XSRF-TOKEN': 'fresh cookie token' });
    });

    it('uses the meta token when the XSRF cookie is unavailable', () => {
        const meta = document.createElement('meta');
        meta.name = 'csrf-token';
        meta.content = 'meta-token';
        document.head.appendChild(meta);

        expect(cashflowCsrfHeaders()).toEqual({ 'X-CSRF-TOKEN': 'meta-token' });
    });

    it('reports a redirected HTML response as an expired session', async () => {
        const response = {
            ok: true,
            status: 200,
            redirected: true,
            headers: new Headers({ 'content-type': 'text/html; charset=UTF-8' }),
        } as Response;

        await expect(cashflowResponsePayload(response, 'Preview import gagal.')).rejects.toThrow(
            'Session expired. Reload page and try again.',
        );
    });

    it('reports a JSON 419 response as an expired session', async () => {
        const response = {
            ok: false,
            status: 419,
            redirected: false,
            headers: new Headers({ 'content-type': 'application/json' }),
            json: async () => ({ message: 'CSRF token mismatch.' }),
        } as Response;

        await expect(cashflowResponsePayload(response, 'Preview import gagal.')).rejects.toThrow(
            'Session expired. Reload page and try again.',
        );
    });

    it('keeps an HTML 500 response as an import failure', async () => {
        const response = {
            ok: false,
            status: 500,
            redirected: false,
            headers: new Headers({ 'content-type': 'text/html; charset=UTF-8' }),
        } as Response;

        await expect(cashflowResponsePayload(response, 'Preview import gagal.')).rejects.toThrow(
            'Preview import gagal.',
        );
    });

    it('preserves a backend JSON error message', async () => {
        const response = {
            ok: false,
            status: 422,
            redirected: false,
            headers: new Headers({ 'content-type': 'application/json' }),
            json: async () => ({ message: 'Action tidak sesuai template departemen.' }),
        } as Response;

        await expect(cashflowResponsePayload(response, 'Confirm import gagal.')).rejects.toThrow(
            'Action tidak sesuai template departemen.',
        );
    });
});
