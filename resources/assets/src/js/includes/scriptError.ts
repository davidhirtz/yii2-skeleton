// Errors happening only in other people's browsers reach the server log, and through it Sentry (monorepo issue #559).
// `reportError()` dispatches `error` too, so an initialiser `onLoad()` caught is reported as well.
export const maxReports = 10;

const reported = new Set<string>();

export const reportScriptError = (fields: {message: string; file?: string; line?: number; column?: number; stack?: string}) => {
    const url = document.body.dataset.scriptErrorUrl;
    const param = document.body.dataset.csrfParam;
    const headers = document.getElementById('wrap')?.getAttribute('hx-headers:inherited');
    const key = [fields.message, fields.file, fields.line, fields.column].join('|');

    if (!url || !param || !headers || reported.size >= maxReports || reported.has(key)) {
        return;
    }

    reported.add(key);

    const data = new FormData();

    data.set(param, Object.values(JSON.parse(headers) as Record<string, string>).pop() ?? '');
    data.set('message', fields.message);
    data.set('file', fields.file ?? '');
    data.set('line', String(fields.line ?? ''));
    data.set('column', String(fields.column ?? ''));
    data.set('stack', (fields.stack ?? '').slice(0, 8000));
    data.set('url', location.href);
    data.set('userAgent', navigator.userAgent);

    navigator.sendBeacon(url, data);
};

export const resetScriptErrors = () => reported.clear();

window.addEventListener('error', (event: ErrorEvent) => {
    reportScriptError({
        message: event.message || String(event.error),
        file: event.filename,
        line: event.lineno,
        column: event.colno,
        stack: event.error instanceof Error ? event.error.stack : undefined,
    });
});

window.addEventListener('unhandledrejection', (event: PromiseRejectionEvent) => {
    const {reason} = event;

    reportScriptError({
        message: `Unhandled rejection: ${reason instanceof Error ? reason.message : String(reason)}`,
        stack: reason instanceof Error ? reason.stack : undefined,
    });
});
