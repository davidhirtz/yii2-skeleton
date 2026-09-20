const COOKIE_NAME = '_theme';
const COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

/**
 * Switching the scheme re-renders nothing, so nothing here is an htmx request: the cookie and the attribute are
 * written on the spot and the account column, if there is one, is caught up afterwards. The cookie is host-only
 * and carries the `Secure` flag the server rendered — deriving it from the protocol would plant a twin on a host
 * that answers on both.
 */
export default ($dropdown: HTMLElement) => {
    const secure = $dropdown.hasAttribute('data-color-scheme-secure') ? '; secure' : '';
    const url = $dropdown.dataset.colorSchemeUrl;

    const $options = $dropdown.querySelectorAll<HTMLButtonElement>('[data-color-scheme-value]');
    const $icon = $dropdown.querySelector<HTMLElement>('[data-color-scheme-icon] .fas');

    const write = (scheme: string) => {
        const age = scheme ? COOKIE_MAX_AGE : 0;
        document.cookie = `${COOKIE_NAME}=${scheme}; path=/; max-age=${age}; samesite=lax${secure}`;
    };

    const persist = (scheme: string) => {
        if (!url) {
            return;
        }

        const body = new FormData();
        body.set('colorScheme', scheme);

        // A `fetch()` inherits nothing, and the navbar is outside `#wrap` so it would inherit nothing anyway. The
        // token is read here rather than at module scope: `#wrap` is re-rendered with a fresh one on every swap.
        const headers = new Headers();
        headers.set('X-CSRF-Token', Object.values(JSON.parse(document.querySelector('#wrap')!.getAttribute('hx-headers:inherited') as string) as Object).pop());

        fetch(url, {body, headers, method: 'POST'}).catch(() => undefined);
    };

    $options.forEach(($option) => {
        $option.addEventListener('click', () => {
            const scheme = $option.dataset.colorSchemeValue ?? '';

            write(scheme);
            persist(scheme);

            if (scheme) {
                document.documentElement.dataset.theme = scheme;
            } else {
                delete document.documentElement.dataset.theme;
            }

            $options.forEach(($other) => $other.classList.toggle('selected', $other === $option));

            // The option carries the icon for its own scheme, so the trigger is caught up from it rather than
            // from a second copy of the mapping here.
            const $optionIcon = $option.querySelector<HTMLElement>('.fas');

            if ($icon && $optionIcon) {
                $icon.className = $optionIcon.className;
            }

            $dropdown.querySelector<HTMLElement>('[popover]')?.hidePopover();
        });
    });
};
