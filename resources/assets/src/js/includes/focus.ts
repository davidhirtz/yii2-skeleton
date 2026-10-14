/**
 * A swap replaces the element that had the focus, which drops it to `<body>`: a keyboard or screen reader user is
 * sent back to the start of the page. Where it goes instead:
 *
 * - after a save the form rejected, the first field it rejected;
 * - after a form reload (a type change), the control that caused it, found again by its id;
 * - after a save that redirected back to the page it was made on, the field that had the focus, or nothing;
 * - after a navigation, which swaps `#wrap`, nothing.
 *
 * The swap replaced the target, so the page is asked rather than the element the request was issued from.
 */
let focusedId: string | null = null;
let focusedUrl: string | null = null;

// The redirect of a save lists its query parameters in its own order.
const normalizeUrl = (href: string): string => {
    const url = new URL(href, document.baseURI);
    url.searchParams.sort();

    return url.pathname + url.search;
};

export const rememberFocus = (): void => {
    focusedId = document.activeElement instanceof HTMLElement && document.activeElement !== document.body
        ? document.activeElement.id || null
        : null;
    focusedUrl = normalizeUrl(location.href);
};

export const focusFirstInvalid = (method?: string): boolean => {
    if (method !== 'POST') {
        return false;
    }

    const $invalid = document.querySelector<HTMLElement>('#wrap [aria-invalid="true"]');
    $invalid?.focus();

    return !!$invalid;
};

interface SwapContext {
    target?: Element | null;
    request?: {method?: string; headers?: Record<string, string>};
}

export const restoreFocus = (ctx?: SwapContext): void => {
    if (!ctx || focusFirstInvalid(ctx.request?.method)) {
        return;
    }

    const $previous = focusedId ? document.getElementById(focusedId) : null;

    if (ctx.request?.headers?.['X-Form-Reload'] && $previous) {
        $previous.focus();
        return;
    }

    // A save's redirect is a GET of its own (`HX-Location`), issued while the focus is still in the form, and htmx
    // has pushed its URL by now: landing on the same URL is the page answering, not a navigation.
    if (focusedUrl === normalizeUrl(location.href)) {
        $previous?.focus({preventScroll: true});
        return;
    }

    if (ctx.target?.id === 'wrap' || ctx.target === document.body) {
        return;
    }

    // A narrower swap — a grid's filter, sort or pager — keeps the focus where the same control is rendered again.
    if ($previous && document.activeElement === document.body) {
        $previous.focus({preventScroll: true});
    }
};
