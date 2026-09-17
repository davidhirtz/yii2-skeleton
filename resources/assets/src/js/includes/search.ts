import htmx from 'htmx.org';

import {openUnder} from "./popover";

// The navbar is outside the swapped region, so this runs once and the element survives every boosted navigation.
export default ($container: HTMLElement) => {
    if ($container.dataset.searchReady) {
        return;
    }

    $container.dataset.searchReady = 'true';

    const $toggle = $container.querySelector('[data-search-toggle]') as HTMLElement | null;
    const $input = $container.querySelector('input[name="q"]') as HTMLInputElement | null;
    const $results = $container.querySelector('[data-search-results]') as HTMLElement | null;

    if (!$toggle || !$input || !$results) {
        return;
    }

    let teardown: (() => void) | null = null;

    const items = () => [...$results.querySelectorAll('a')] as HTMLAnchorElement[];

    const open = () => {
        teardown = openUnder($input, $results) ?? teardown;
    };

    const close = () => {
        if ($results.matches(':popover-open')) {
            $results.hidePopover();
        }

        teardown?.();
        teardown = null;
    };

    // Below the navbar's own breakpoint the open box covers the rest of the bar, the aside toggle included.
    const isOverlay = () => getComputedStyle($container).getPropertyValue('--navbar-search-overlay').trim() === '1';

    const expand = () => {
        $container.classList.add('expanded');
        $toggle.setAttribute('aria-expanded', 'true');
        $input.focus();
    };

    const collapse = () => {
        close();
        $results.innerHTML = '';
        $container.classList.remove('expanded');
        $toggle.setAttribute('aria-expanded', 'false');
    };

    $toggle.addEventListener('click', () => {
        $container.classList.contains('expanded') ? collapse() : expand();
    });

    // A manual popover gets neither light dismiss nor the Escape key, so both are handled on the document — and
    // the keystroke has to be caught there anyway once focus has left the box.
    document.addEventListener('keydown', (event: KeyboardEvent) => {
        if (event.key === 'Escape' && $container.classList.contains('expanded')) {
            $input.value = '';
            collapse();
        }
    });

    document.addEventListener('pointerdown', (event: Event) => {
        if (!$container.contains(event.target as Node)) {
            close();

            if (!$input.value) {
                collapse();
            }
        }
    });

    $container.addEventListener('keydown', (event: KeyboardEvent) => {
        if (event.key === 'Escape') {
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();

            const $focused = document.activeElement;

            if ($focused instanceof HTMLAnchorElement && $results.contains($focused)) {
                $focused.click();
            } else if ($input.value.trim()) {
                // Target, select and swap come from the body like a boosted link's; the push URL from the container.
                htmx.ajax('GET', `${$container.dataset.search}?q=${encodeURIComponent($input.value.trim())}`, {
                    source: $container,
                });
            }

            return;
        }

        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
            return;
        }

        const $items = items();

        if (!$items.length) {
            return;
        }

        event.preventDefault();

        const index = $items.indexOf(document.activeElement as HTMLAnchorElement);
        const next = index + (event.key === 'ArrowDown' ? 1 : -1);

        next < 0 ? $input.focus() : $items[Math.min(next, $items.length - 1)].focus();
    });

    // A click on a result has to land before the blur collapses the input again.
    $container.addEventListener('focusout', () => {
        requestAnimationFrame(() => {
            if (!$input.value && !$container.contains(document.activeElement)) {
                collapse();
            }
        });
    });

    // The results page renders the query back into the input, so the box has to open with it — without stealing the
    // focus, and not where it would leave the menu behind an overlay the page was not asked to put there.
    if ($input.value && !isOverlay()) {
        $container.classList.add('expanded');
        $toggle.setAttribute('aria-expanded', 'true');
    }

    $results.addEventListener('htmx:afterSwap', () => {
        $results.childElementCount ? open() : close();
    });

    // htmx pushes the URL before it swaps, so the location already names the page landed on.
    const searchPath = new URL($container.dataset.search!, location.origin).pathname;

    document.body.addEventListener('htmx:afterSwap', (event: Event) => {
        if ($container.contains(event.target as Node)) {
            return;
        }

        if (location.pathname === searchPath) {
            close();
            return;
        }

        $input.value = '';
        collapse();
    });
}
