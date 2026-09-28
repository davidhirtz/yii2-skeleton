/**
 * A refused save leaves its warning among the flashes, outside the swapped page. Saving again is the editor's
 * answer to it, so submitting the form (`data-stale-save`, holding the message) takes that flash away.
 */
export default ($form: HTMLFormElement): void => {
    $form.addEventListener('submit', () => {
        const message = $form.dataset.staleSave;

        document.querySelectorAll<HTMLElement>('#flashes flash-alert').forEach(($flash) => {
            if ($flash.querySelector('.alert-content')?.textContent?.trim() === message) {
                $flash.remove();
            }
        });
    });
};
