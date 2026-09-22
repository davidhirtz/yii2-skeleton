const COOKIE_NAME = '_aside';
const COOKIE_MAX_AGE = 60 * 60 * 24 * 365;
const COLLAPSED = 'collapsed';
const COLLAPSED_ATTRIBUTE = 'data-aside-collapsed';
const OPEN_ATTRIBUTE = 'data-aside-open';

/**
 * Collapsing the aside re-renders nothing, so nothing here is an htmx request: the attribute sits on `<html>`,
 * which no swap of `#wrap` touches, and the cookie is what the layout reads back on the next full load. It is
 * host-only and carries the `Secure` flag the server rendered, rather than deriving it from the protocol: a host
 * answering on both would otherwise be left with a twin no plain-http response can overwrite.
 */
export const asidePin = ($btn: HTMLButtonElement) => {
    const secure = $btn.hasAttribute('data-aside-pin-secure') ? '; secure' : '';

    $btn.addEventListener('click', () => {
        const collapsed = !document.documentElement.hasAttribute(COLLAPSED_ATTRIBUTE);
        const label = (collapsed ? $btn.dataset.asidePinLabelCollapsed : $btn.dataset.asidePinLabel) ?? '';

        document.documentElement.toggleAttribute(COLLAPSED_ATTRIBUTE, collapsed);
        // The button sits in the navbar, so the listener below already drops the latch — but a click that never
        // reaches it (a keyboard activation on a page htmx has just swapped) would leave one standing.
        document.documentElement.removeAttribute(OPEN_ATTRIBUTE);

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
 * `data-aside-open` is the whole of it, at every width: the drawer below `md`, and the collapsed rail above it
 * where hover opens the menu but is gone the moment the pointer leaves. A click inside the aside latches it
 * open, which is what makes a submenu item reachable on the page that click navigates to — the attribute is on
 * `<html>`, outside `#wrap`, so the swap leaves it standing.
 *
 * One listener for the three cases, registered at module scope rather than per element: a module is evaluated
 * once per document while `onLoad` runs again for every swap, which would otherwise stack a copy per
 * navigation. It is also why the toggle and the backdrop are read here rather than bound individually — a
 * handler of their own would set the state this listener then cleared, both being outside the aside.
 */
document.addEventListener('click', (event: MouseEvent) => {
    const $target = event.target instanceof Element ? event.target : null;

    // The navbar toggle and the backdrop behind the drawer, which both carry `data-aside`.
    if ($target?.closest('[data-aside]')) {
        document.documentElement.toggleAttribute(OPEN_ATTRIBUTE);
        return;
    }

    const latch = !!$target?.closest('#aside');

    document.documentElement.toggleAttribute(OPEN_ATTRIBUTE, latch);
});

document.addEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        document.documentElement.removeAttribute(OPEN_ATTRIBUTE);
    }
});
