import {computePosition, autoUpdate, flip, offset, shift} from '@floating-ui/dom';

export default ($btn: HTMLElement) => {
    const selector = $btn.dataset.dropdown;
    const $dialog: HTMLDialogElement | null = selector
        ? document.querySelector(selector)
        : $btn.parentElement.querySelector('dialog');

    const keydownEvent = (event: KeyboardEvent) => {
        const $current = document.activeElement.closest('li');

        if ($current) {
            if (event.key === 'ArrowDown') {
                (($current.nextElementSibling || $current.parentElement.firstElementChild).firstElementChild as HTMLButtonElement).focus();
            } else if (event.key === 'ArrowUp') {
                (($current.previousElementSibling || $current.parentElement.lastElementChild).firstElementChild as HTMLButtonElement).focus();
            }
        }
    }

    const clickOutsideEvent = (event: MouseEvent) => {
        if (!$dialog.firstElementChild.contains(event.target as HTMLElement)) {
            $dialog.close();
        }
    }

    let cleanup: () => void;

    $btn.addEventListener('click', () => {
        $dialog.showModal();
        $dialog.addEventListener('keydown', keydownEvent);
        $dialog.addEventListener('click', clickOutsideEvent);

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