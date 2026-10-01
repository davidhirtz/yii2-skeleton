// Every button toggling the target says whether it is open, which is what a screen reader announces.
export default ($owner: HTMLElement) => {
    $owner.addEventListener('click', () => {
        const selector = $owner.dataset.collapse;
        const $target = selector ? document.querySelector<HTMLElement>(selector) : null;

        if ($target) {
            const expanded = $target.classList.toggle('collapsed') ? 'false' : 'true';

            document.querySelectorAll(`[data-collapse="${CSS.escape(selector!)}"]`)
                .forEach(($button) => $button.setAttribute('aria-expanded', expanded));
        }
    });
}
