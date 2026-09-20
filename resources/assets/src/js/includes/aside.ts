const COOKIE_NAME = '_aside';
const COOKIE_MAX_AGE = 60 * 60 * 24 * 365;
const COLLAPSED = 'collapsed';
const COLLAPSED_ATTRIBUTE = 'data-aside-collapsed';
const OPEN_ATTRIBUTE = 'data-aside-open';

/**
 * The drawer below `md`, where the aside is out of flow and the toggle in the navbar is the only way to it.
 */
export const asideToggle = ($btn: HTMLButtonElement) => {
    $btn.addEventListener('click', () => document.body.classList.toggle('has-aside'));
};

/**
 * Collapsing the aside re-renders nothing, so nothing here is an htmx request: the attribute sits on `<html>`,
 * which no swap of `#wrap` touches, and the cookie is what the layout reads back on the next full load. It is
 * host-only and carries the `Secure` flag the server rendered, for the reason `includes/colorScheme.ts` gives.
 */
export const asidePin = ($btn: HTMLButtonElement) => {
    const secure = $btn.hasAttribute('data-aside-pin-secure') ? '; secure' : '';

    $btn.addEventListener('click', (event: MouseEvent) => {
        const collapsed = !document.documentElement.hasAttribute(COLLAPSED_ATTRIBUTE);
        const label = (collapsed ? $btn.dataset.asidePinLabelCollapsed : $btn.dataset.asidePinLabel) ?? '';

        document.documentElement.toggleAttribute(COLLAPSED_ATTRIBUTE, collapsed);
        // The latch below skips this button, but one from an earlier click would still be standing.
        document.documentElement.removeAttribute(OPEN_ATTRIBUTE);

        // The button keeps focus after a pointer click, and `.aside:focus-within` is one of the three ways the
        // collapsed menu is open — so collapsing would appear to do nothing until the next click elsewhere. A
        // keyboard activation reports no detail and keeps its focus, where staying open is the right answer.
        if (collapsed && event.detail > 0) {
            $btn.blur();
        }

        document.cookie = `${COOKIE_NAME}=${collapsed ? COLLAPSED : ''}; path=/; max-age=${collapsed ? COOKIE_MAX_AGE : 0}; samesite=lax${secure}`;

        $btn.setAttribute('aria-pressed', collapsed ? 'false' : 'true');
        $btn.setAttribute('aria-label', label);

        const $icon = $btn.querySelector<HTMLElement>('.fas');
        $icon?.classList.toggle('fa-thumbtack', !collapsed);
        $icon?.classList.toggle('fa-thumbtack-slash', collapsed);

        // `includes/tooltips.ts` bakes the `title` into an element it inserts after the button on `mouseenter`,
        // which is where the pointer is at this very moment — so the open tooltip is caught up here rather than
        // through an attribute it has already consumed.
        const $inner = $btn.nextElementSibling?.querySelector('.tooltip-inner');

        if ($inner) {
            $inner.textContent = label;
        }
    });
};

/**
 * A collapsed aside opens on hover, which is gone the moment the pointer leaves — so a click inside it latches
 * it open until the next click outside, which is what makes a submenu item reachable. The attribute is on
 * `<html>`, outside `#wrap`, so the swap the click triggers leaves it standing.
 *
 * These two are registered at module scope rather than per element: a module is evaluated once per document
 * while `onLoad` runs again for every swap, which would otherwise stack a copy per navigation.
 */
document.addEventListener('click', (event: MouseEvent) => {
    const $target = event.target instanceof Element ? event.target : null;

    // The pin button is inside the aside but is the one control that means "stop showing this", so a click on
    // it must not latch what it just collapsed.
    const latch = !!$target?.closest('#aside') && !$target.closest('[data-aside-pin]');

    document.documentElement.toggleAttribute(OPEN_ATTRIBUTE, latch);
});

document.addEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        document.documentElement.removeAttribute(OPEN_ATTRIBUTE);
    }
});
