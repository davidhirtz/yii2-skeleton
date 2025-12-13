export default ($owner: HTMLElement) => {
    $owner.addEventListener('click', () => {
        const selector = $owner.dataset.collapse;
        const $target = selector ? (document.querySelector(selector) as HTMLElement) : null;

        if ($target) {
            $target.ariaExpanded = $target.classList.toggle('collapsed') ? 'false' : 'true';
        }
    });
}