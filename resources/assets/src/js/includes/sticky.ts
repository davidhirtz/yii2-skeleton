const items = new Set<HTMLElement>();
let bound = false;

const update = (): void => {
    items.forEach(($el) => {
        if (!$el.isConnected) {
            items.delete($el);
            return;
        }

        const rect = $el.getBoundingClientRect();
        const stuck = $el.dataset.sticky === 'bottom'
            ? rect.bottom >= window.innerHeight
            : rect.top <= 0;

        $el.classList.toggle('sticky', stuck);
    });
};

export default ($el: HTMLElement): void => {
    items.add($el);

    if (!bound) {
        document.addEventListener('scroll', update, {passive: true});
        window.addEventListener('resize', update);
        bound = true;
    }

    update();
};
