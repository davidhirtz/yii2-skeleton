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

export const createModal = (title:string, html: string): HTMLDialogElement => {
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