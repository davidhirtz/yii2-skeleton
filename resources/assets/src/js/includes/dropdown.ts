export default ($btn: HTMLElement) => {
    const $popover = document.getElementById($btn.getAttribute('popovertarget')!) as HTMLElement | null;

    if (!$popover) {
        return;
    }

    const $items = $popover.querySelectorAll('a:not([inert],.disabled),button:not([inert],:disabled,.disabled),input:not([inert],:disabled,.disabled)') as NodeListOf<HTMLElement>;
    let selected = 0;

    const keydownEvent = (event: KeyboardEvent) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            const $visibleItems = [...$items].filter($item => $item.checkVisibility());

            selected = (selected + (event.key === 'ArrowDown' ? 1 : -1) + $visibleItems.length) % $visibleItems.length;
            $visibleItems[selected].focus();
        }
    }

    $popover.addEventListener('toggle', (event) => {
        if ((event as ToggleEvent).newState === 'open') {
            $popover.addEventListener('keydown', keydownEvent);

            if ($btn.dataset.autofocus) {
                $items[selected].focus();
            }

            const rect = $btn.getBoundingClientRect();
            const fitsBelow = rect.bottom + 4 + $popover.offsetHeight <= window.innerHeight;

            Object.assign($popover.style, {
                left: `${rect.left}px`,
                top: fitsBelow ? `${rect.bottom + 4}px` : `${rect.top - 4 - $popover.offsetHeight}px`,
                width: `${rect.width}px`,
            });
        } else {
            $popover.removeEventListener('keydown', keydownEvent);
        }
    });
}