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
            // htmx 4 dropped `hx-vars`, and reads `hx-vals` off the element at request time — so setting it here,
            // after htmx has already processed the modal, is what the button posts.
            $button.setAttribute('hx-vals', JSON.stringify({timezone}));
        }

        $modal.showModal();
    }
};
