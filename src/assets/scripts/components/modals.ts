export default ($trigger: NodeListOf<HTMLDivElement>) => {
    $trigger.forEach(($el: HTMLElement) => $el.addEventListener('click', () => {
        const selector = $el.dataset.modal;
        const $target = selector
            ? (document.querySelector(selector) as HTMLElement)
            : $el.closest('[open]');

        if ($target) {
            $target[selector ? 'showModal' : 'close']();
        }
    }));
}