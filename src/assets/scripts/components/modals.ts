export default ($owner: HTMLElement) => {
    $owner.addEventListener('click', () => {
        const selector = $owner.dataset.modal;
        const $target = selector
            ? (document.querySelector(selector) as HTMLElement)
            : $owner.closest('[open]');

        if ($target) {
            $target[selector ? 'showModal' : 'close']();
        }
    });
}