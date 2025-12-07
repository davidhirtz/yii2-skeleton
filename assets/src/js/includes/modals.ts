export default ($owner: HTMLButtonElement) => {
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