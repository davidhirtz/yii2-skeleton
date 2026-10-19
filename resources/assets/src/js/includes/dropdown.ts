import {autoUpdate, computePosition, flip, offset, shift} from "@floating-ui/dom";

import {lockScroll, unlockScroll} from "./scrollLock";
import {teardownOnDisconnect} from "./teardown";

// Listeners on the document, not per button from `onLoad`: `popovertarget` opens a menu without any script, so a
// button an initialiser's error left unwired opened its menu in the top left corner (monorepo issue #559). Neither
// event bubbles, hence the capture phase.
const teardowns = new WeakMap<HTMLElement, () => void>();

const getButton = (event: Event, $popover: HTMLElement): HTMLElement | null => {
    const $source = (event as ToggleEvent & {source?: Element | null}).source;

    if ($source instanceof HTMLElement) {
        return $source.hasAttribute('popovertarget') ? $source : null;
    }

    return $popover.id
        ? document.querySelector<HTMLElement>(`[popovertarget="${CSS.escape($popover.id)}"]`)
        : null;
};

const open = ($btn: HTMLElement, $popover: HTMLElement) => {
    const $items = $popover.querySelectorAll('a:not([inert],.disabled),button:not([inert],:disabled,.disabled),input:not([inert],:disabled,.disabled)') as NodeListOf<HTMLElement>;
    const $dropdown = $btn.closest('.dropdown');
    const preferred = $dropdown?.classList.contains('dropup') ? 'top-start' : 'bottom-start';
    let selected = 0;

    // `selected` indexes this list, not `$items` — the filter dropdown hides items as it is typed in.
    const visibleItems = () => [...$items].filter($item => $item.checkVisibility());

    const keydownEvent = (event: KeyboardEvent) => {
        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
            return;
        }

        const $visibleItems = visibleItems();

        if (!$visibleItems.length) {
            return;
        }

        selected = (selected + (event.key === 'ArrowDown' ? 1 : -1) + $visibleItems.length) % $visibleItems.length;
        $visibleItems[selected].focus();
    }

    const updatePosition = () => {
        computePosition($btn, $popover, {
            placement: preferred,
            middleware: [offset(4), flip(), shift({padding: 4})],
        }).then(({x, y, placement}) => {
            Object.assign($popover.style, {
                left: `${x}px`,
                top: `${y}px`,
                visibility: 'visible',
            });

            // A floor rather than the width, so a menu wider than its button grows instead of wrapping its labels.
            $popover.style.setProperty('--dropdown-anchor-width', `${$btn.offsetWidth}px`);

            $dropdown?.classList.toggle('dropup', placement.startsWith('top'));
        });
    }

    const cleanup = autoUpdate($btn, $popover, updatePosition);

    $popover.addEventListener('keydown', keydownEvent);
    lockScroll($popover);

    teardowns.set($popover, teardownOnDisconnect($popover, () => {
        $popover.removeEventListener('keydown', keydownEvent);
        unlockScroll($popover);
        cleanup();
    }));

    if ($btn.hasAttribute('data-autofocus')) {
        requestAnimationFrame(() => visibleItems()[selected]?.focus());
    }
}

document.addEventListener('beforetoggle', (event) => {
    const $popover = event.target;

    if ($popover instanceof HTMLElement && (event as ToggleEvent).newState === 'open' && getButton(event, $popover)) {
        $popover.style.visibility = 'hidden';
    }
}, true);

document.addEventListener('toggle', (event) => {
    const $popover = event.target;

    if (!($popover instanceof HTMLElement)) {
        return;
    }

    if ((event as ToggleEvent).newState !== 'open') {
        teardowns.get($popover)?.();
        teardowns.delete($popover);
        return;
    }

    const $btn = getButton(event, $popover);

    if ($btn) {
        teardowns.get($popover)?.();
        open($btn, $popover);
    }
}, true);
