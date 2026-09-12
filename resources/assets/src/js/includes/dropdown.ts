import {autoUpdate, computePosition, flip, offset, shift} from "@floating-ui/dom";

import {lockScroll, unlockScroll} from "./scrollLock";
import {teardownOnDisconnect} from "./teardown";

export default ($btn: HTMLElement) => {
    const $popover = document.getElementById($btn.getAttribute('popovertarget')!) as HTMLElement | null;

    if (!$popover) {
        return;
    }

    const $items = $popover.querySelectorAll('a:not([inert],.disabled),button:not([inert],:disabled,.disabled),input:not([inert],:disabled,.disabled)') as NodeListOf<HTMLElement>;
    const $dropdown = $btn.closest('.dropdown');
    const preferred = $dropdown?.classList.contains('dropup') ? 'top-start' : 'bottom-start';
    let selected = 0;
    let teardown: (() => void) | null = null;

    const keydownEvent = (event: KeyboardEvent) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            const $visibleItems = [...$items].filter($item => $item.checkVisibility());

            selected = (selected + (event.key === 'ArrowDown' ? 1 : -1) + $visibleItems.length) % $visibleItems.length;
            $visibleItems[selected].focus();
        }
    }

    const updatePosition = () => {
        computePosition($btn, $popover, {
            placement: preferred,
            middleware: [offset(4), flip(), shift({padding: 4})],
        }).then(({x, y, placement}) => {
            Object.assign($popover.style, {
                left: `${x}px`,
                top: `${y}px`,
                width: `${$btn.offsetWidth}px`,
                visibility: 'visible',
            });

            $dropdown?.classList.toggle('dropup', placement.startsWith('top'));
        });
    }

    $popover.addEventListener('beforetoggle', (event) => {
        if ((event as ToggleEvent).newState === 'open') {
            $popover.style.visibility = 'hidden';
        }
    });

    $popover.addEventListener('toggle', (event) => {
        if ((event as ToggleEvent).newState === 'open') {
            const cleanup = autoUpdate($btn, $popover, updatePosition);

            $popover.addEventListener('keydown', keydownEvent);
            lockScroll($popover);

            teardown = teardownOnDisconnect($popover, () => {
                $popover.removeEventListener('keydown', keydownEvent);
                unlockScroll($popover);
                cleanup();
            });

            if ($btn.hasAttribute('data-autofocus')) {
                requestAnimationFrame(() => $items[selected].focus());
            }
        } else {
            $popover.style.visibility = '';
            teardown?.();
            teardown = null;
        }
    });
}
