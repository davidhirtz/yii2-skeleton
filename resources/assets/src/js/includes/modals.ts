export const closeModal = ($owner: HTMLButtonElement) => {
    $owner.addEventListener('click', () => {
        const selector = $owner.dataset.modal;
        const $target = (selector ? document.querySelector(selector) : $owner.closest('[open]')) as HTMLDialogElement | null;

        if ($target) {
            if(selector && (!$owner.form || $owner.form.reportValidity())) {
               $target.showModal();
            } else {
                $target.close();
            }
        }
    });
}

/**
 * With a close label, the header carries the close button the server's `Modal` widget renders.
 */
export const createModal = (title:string, html: string, closeLabel?: string): HTMLDialogElement => {
    const $dialog = document.createElement('dialog');
    const $header = document.createElement('div');
    const $title = document.createElement('div');
    const $body = document.createElement('div');

    $dialog.className = 'modal';
    $header.className = 'modal-header';

    $title.className = 'modal-title';
    $title.textContent = title;

    $body.className = 'modal-body';
    $body.innerHTML = html;

    $header.appendChild($title);

    if (closeLabel) {
        const $close = document.createElement('button');
        const $icon = document.createElement('span');

        $close.type = 'button';
        $close.className = 'btn-icon icon';
        $close.setAttribute('aria-label', closeLabel);
        $close.addEventListener('click', () => $dialog.close());

        $icon.className = 'fas fa-xmark';
        $close.appendChild($icon);
        $header.appendChild($close);
    }

    $dialog.appendChild($header);
    $dialog.appendChild($body);

    $dialog.addEventListener('click', (e) => {
        if (e.target === $dialog) {
            $dialog.close();
        }
    });

    $dialog.addEventListener('close', () => $dialog.remove());

    document.body.appendChild($dialog);
    $dialog.showModal();

    return $dialog;
}