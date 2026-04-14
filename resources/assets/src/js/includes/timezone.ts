export default ($modal: HTMLDialogElement) => {
    const offset = parseInt($modal.dataset.timezoneOffset!);
    const $span = $modal.querySelector<HTMLElement>('[data-timezone]');
    const $button = $modal.querySelector<HTMLButtonElement>('[data-timezone-button]');

    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const browserOffset = -new Date().getTimezoneOffset() * 60;

    if (browserOffset !== offset) {
        if ($span) {
            $span.innerText = timezone;
        }

        if ($button) {
            $button.setAttribute('hx-vars', JSON.stringify({timezone}));
        }

        $modal.showModal();
    }
};