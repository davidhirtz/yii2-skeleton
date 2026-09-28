/**
 * A swap replaces the element that had the focus, which drops it to `<body>`: a keyboard or screen reader user is
 * sent back to the start of the page. Where it goes instead:
 *
 * - after a save the form rejected, the first field it rejected;
 * - after a form reload (a type change), the control that caused it, found again by its id;
 * - after a navigation, which swaps `#wrap`, the page's heading.
 *
 * The swap replaced the target, so the page is asked rather than the element the request was issued from.
 */
let focusedId: string | null = null;

export const rememberFocus = (): void => {
    focusedId = document.activeElement instanceof HTMLElement && document.activeElement !== document.body
        ? document.activeElement.id || null
        : null;
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

    if (ctx.target?.id === 'wrap' || ctx.target === document.body) {
        const $heading = document.querySelector<HTMLElement>('#wrap h1');

        if ($heading) {
            $heading.tabIndex = -1;
            $heading.focus({preventScroll: true});
        }

        return;
    }

    // A narrower swap — a grid's filter, sort or pager — keeps the focus where the same control is rendered again.
    if ($previous && document.activeElement === document.body) {
        $previous.focus({preventScroll: true});
    }
};
