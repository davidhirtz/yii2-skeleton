import {autoUpdate, computePosition, flip, offset, shift} from "@floating-ui/dom";
import htmx from 'htmx.org';

import {teardownOnDisconnect} from "./teardown";

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

    const updatePosition = () => computePosition($input, $results, {
        placement: 'bottom-start',
        middleware: [offset(4), flip(), shift({padding: 8})],
    }).then(({x, y}) => {
        Object.assign($results.style, {
            left: `${x}px`,
            minWidth: `${$input.offsetWidth}px`,
            top: `${y}px`,
        });
    });

    const open = () => {
        if ($results.matches(':popover-open')) {
            updatePosition();
            return;
        }

        $results.showPopover();

        const cleanup = autoUpdate($input, $results, updatePosition);
        teardown = teardownOnDisconnect($results, cleanup);
    };

    const close = () => {
        if ($results.matches(':popover-open')) {
            $results.hidePopover();
        }

        teardown?.();
        teardown = null;
    };

    const expand = () => {
        $container.classList.add('expanded');
        $input.focus();
    };

    const collapse = () => {
        close();
        $results.innerHTML = '';
        $container.classList.remove('expanded');
    };

    $toggle.addEventListener('click', () => {
        $container.classList.contains('expanded') ? collapse() : expand();
    });

    $container.addEventListener('keydown', (event: KeyboardEvent) => {
        if (event.key === 'Escape') {
            $input.value = '';
            collapse();
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

    // The results page renders the query back into the input, so the box has to open with it.
    if ($input.value) {
        $container.classList.add('expanded');
    }

    $results.addEventListener('htmx:afterSwap', () => {
        $results.childElementCount ? open() : close();
    });

    document.body.addEventListener('htmx:afterSwap', (event: Event) => {
        if ($container.contains(event.target as Node)) {
            return;
        }

        close();

        if (!$input.value) {
            $container.classList.remove('expanded');
        }
    });
}
