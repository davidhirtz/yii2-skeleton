import {beforeEach, describe, expect, it, vi} from 'vitest';
import {maxReports, resetScriptErrors} from '../../../src/js/includes/scriptError';

const sendBeacon = vi.fn((_url: string, _data: FormData) => true);

const raise = (message: string, line = 1) => {
    window.dispatchEvent(new ErrorEvent('error', {
        message,
        filename: 'https://www.test.localhost/assets/admin.js',
        lineno: line,
        colno: 7,
        error: new TypeError(message),
    }));
};

describe('scriptError', () => {
    beforeEach(() => {
        Object.defineProperty(navigator, 'sendBeacon', {value: sendBeacon, configurable: true});
        sendBeacon.mockClear();
        resetScriptErrors();

        document.body.dataset.scriptErrorUrl = '/admin/script-error/create';
        document.body.dataset.csrfParam = '_csrf';
        document.body.innerHTML = `<div id="wrap" hx-headers:inherited='{"X-CSRF-TOKEN":"token"}'></div>`;
    });

    it('sends an error with the CSRF token', () => {
        raise('x is undefined');

        expect(sendBeacon).toHaveBeenCalledOnce();

        const [url, data] = sendBeacon.mock.calls[0];

        expect(url).toBe('/admin/script-error/create');
        expect(data.get('_csrf')).toBe('token');
        expect(data.get('message')).toBe('x is undefined');
        expect(data.get('file')).toBe('https://www.test.localhost/assets/admin.js');
        expect(data.get('line')).toBe('1');
        expect(data.get('column')).toBe('7');
        expect(data.get('stack')).toContain('x is undefined');
        expect(data.get('url')).toBe(location.href);
        expect(data.get('userAgent')).toBe(navigator.userAgent);
    });

    it('sends an unhandled rejection', () => {
        const event = Object.assign(new Event('unhandledrejection'), {reason: new Error('Ünïcødé 🙂 failed')});
        window.dispatchEvent(event);

        expect(sendBeacon.mock.calls[0][1].get('message')).toBe('Unhandled rejection: Ünïcødé 🙂 failed');
    });

    it('sends the same error once', () => {
        raise('repeated');
        raise('repeated');

        expect(sendBeacon).toHaveBeenCalledOnce();
    });

    it('stops after the limit', () => {
        for (let line = 1; line <= maxReports + 5; line++) {
            raise('error', line);
        }

        expect(sendBeacon).toHaveBeenCalledTimes(maxReports);
    });

    it('sends nothing without an endpoint', () => {
        delete document.body.dataset.scriptErrorUrl;
        raise('guest');

        expect(sendBeacon).not.toHaveBeenCalled();
    });
});
