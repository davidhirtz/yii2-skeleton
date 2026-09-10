import {autoUpdate, computePosition, flip, offset, shift} from "@floating-ui/dom";

export default ($btn: HTMLElement) => {
    const $popover = document.getElementById($btn.getAttribute('popovertarget')!) as HTMLElement | null;

    if (!$popover) {
        return;
    }

    const $items = $popover.querySelectorAll('a:not([inert],.disabled),button:not([inert],:disabled,.disabled),input:not([inert],:disabled,.disabled)') as NodeListOf<HTMLElement>;
    const $dropdown = $btn.closest('.dropdown');
    const preferred = $dropdown?.classList.contains('dropup') ? 'top-start' : 'bottom-start';
    let selected = 0;
    let cleanup: (() => void) | null = null;

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

    const lockScroll = (locked: boolean) => {
        const $html = document.documentElement;
        $html.style.paddingRight = locked ? `${window.innerWidth - $html.clientWidth}px` : '';
        $html.style.overflow = locked ? 'hidden' : '';
    }

    $popover.addEventListener('beforetoggle', (event) => {
        if ((event as ToggleEvent).newState === 'open') {
            $popover.style.visibility = 'hidden';
        }
    });

    $popover.addEventListener('toggle', (event) => {
        if ((event as ToggleEvent).newState === 'open') {
            $popover.addEventListener('keydown', keydownEvent);
            cleanup = autoUpdate($btn, $popover, updatePosition);
            lockScroll(true);

            if ($btn.hasAttribute('data-autofocus')) {
                requestAnimationFrame(() => $items[selected].focus());
            }
        } else {
            $popover.removeEventListener('keydown', keydownEvent);
            $popover.style.visibility = '';
            cleanup?.();
            cleanup = null;
            lockScroll(false);
        }
    });
}
