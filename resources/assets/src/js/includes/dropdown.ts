import {computePosition, autoUpdate, flip, offset} from '@floating-ui/dom';

export default ($btn: HTMLElement) => {
    const selector = $btn.dataset.dropdown;
    const $dialog: HTMLDialogElement | null = selector
        ? document.querySelector(selector)
        : $btn.parentElement!.querySelector('dialog');

    if (!$dialog) {
        return;
    }

    const keydownEvent = (event: KeyboardEvent) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            const $visibleItems = [...$items].filter($item => $item.checkVisibility());

            selected = (selected + (event.key === 'ArrowDown' ? 1 : -1) + $visibleItems.length) % $visibleItems.length;
            $visibleItems[selected].focus();
        }
    }

    const clickOutsideEvent = (event: MouseEvent) => {
        if (!$dialog.firstElementChild!.contains(event.target as HTMLElement)) {
            $dialog.close();
        }
    }

    const $items = $dialog.querySelectorAll('a:not([inert],.disabled),button:not([inert],:disabled,.disabled),input:not([inert],:disabled,.disabled)') as NodeListOf<HTMLElement>;

    let cleanup: () => void;
    let selected = 0;

    $btn.addEventListener('click', () => {
        $dialog.showModal();
        $dialog.addEventListener('click', clickOutsideEvent);

        if ($items) {
            $dialog.addEventListener('keydown', keydownEvent);
            $items[selected].focus();
        }

        cleanup = autoUpdate($btn, $dialog, () => {
            computePosition($btn, $dialog, {
                placement: 'bottom-start',
                middleware: [
                    offset(4),
                    flip({
                        fallbackPlacements: [
                            'bottom-start',
                            'bottom-end',
                            'top-start',
                            'top-end',
                        ],
                        padding: 80,
                    }),
                ],
            }).then(({x, y}) => {
                Object.assign($dialog.style, {
                    left: `${x}px`,
                    top: `${y}px`,
                    width: $btn.offsetWidth + 'px',
                });
            });
        });
    });

    $dialog.addEventListener('close', () => {
        $dialog.removeEventListener('keydown', keydownEvent);
        $dialog.removeEventListener('click', clickOutsideEvent);
        cleanup();
    })
}