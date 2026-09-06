const items = new Set<HTMLElement>();
let bound = false;

const update = (): void => {
    items.forEach(($el) => {
        const $parent = $el.parentElement;

        if (!$parent) {
            items.delete($el);
            return;
        }

        const rect = $parent.getBoundingClientRect();
        const stuck = $el.dataset.sticky === 'bottom'
            ? rect.bottom > window.innerHeight
            : rect.top < 0;

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
