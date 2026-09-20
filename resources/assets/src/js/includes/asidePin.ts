const COOKIE_NAME = '_aside';
const COOKIE_MAX_AGE = 60 * 60 * 24 * 365;
const COLLAPSED = 'collapsed';

/**
 * Collapsing the aside re-renders nothing, so nothing here is an htmx request: the attribute sits on `<html>`,
 * which no swap of `#wrap` touches, and the cookie is what the layout reads back on the next full load. It is
 * host-only and carries the `Secure` flag the server rendered, for the reason `includes/colorScheme.ts` gives.
 */
export default ($btn: HTMLButtonElement) => {
    const secure = $btn.hasAttribute('data-aside-pin-secure') ? '; secure' : '';

    $btn.addEventListener('click', () => {
        const collapsed = !document.documentElement.hasAttribute('data-aside-collapsed');
        const label = (collapsed ? $btn.dataset.asidePinLabelCollapsed : $btn.dataset.asidePinLabel) ?? '';

        document.documentElement.toggleAttribute('data-aside-collapsed', collapsed);
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
