const thresholds = new WeakMap<HTMLElement, number>();
const items = new Set<HTMLElement>();
let bound = false;

const measure = ($el: HTMLElement): void => {
    if ($el.classList.contains('sticky')) {
        return;
    }

    const top = parseInt(getComputedStyle($el).top, 10) || 0;
    thresholds.set($el, $el.getBoundingClientRect().top + window.scrollY - top);
};

const update = (): void => {
    items.forEach(($el) => {
        if (!$el.isConnected) {
            items.delete($el);
            return;
        }

        $el.classList.toggle('sticky', window.scrollY >= (thresholds.get($el) ?? 0));
    });
};

const remeasure = (): void => {
    items.forEach(($el) => $el.isConnected && measure($el));
    update();
};

export default ($el: HTMLElement): void => {
    items.add($el);
    measure($el);

    if (!bound) {
        document.addEventListener('scroll', update, {passive: true});
        window.addEventListener('resize', remeasure);
        bound = true;
    }

    update();
};
