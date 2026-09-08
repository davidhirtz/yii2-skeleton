const metrics = new WeakMap<HTMLElement, {reclaim: number; offset: number}>();
const items = new Set<HTMLElement>();
let bound = false;

// Measures how much height the element reclaims when it collapses (the `.sticky` styling) and its
// document offset, both read in the expanded state with transitions suppressed so the values are exact.
const measure = ($el: HTMLElement): void => {
    if ($el.dataset.sticky === 'bottom') {
        return;
    }

    const wasSticky = $el.classList.contains('sticky');
    const transition = $el.style.transition;

    $el.style.transition = 'none';
    $el.classList.remove('sticky');

    const expanded = $el.offsetHeight;
    const offset = $el.getBoundingClientRect().top + window.scrollY;

    $el.classList.add('sticky');

    const collapsed = $el.offsetHeight;

    $el.classList.toggle('sticky', wasSticky);
    $el.style.transition = transition;

    metrics.set($el, {reclaim: Math.max(0, expanded - collapsed), offset});
};

const update = (): void => {
    const viewport = window.innerHeight;
    const maxScroll = document.documentElement.scrollHeight - viewport;

    items.forEach(($el) => {
        if (!$el.isConnected) {
            items.delete($el);
            return;
        }

        const rect = $el.getBoundingClientRect();

        if ($el.dataset.sticky === 'bottom') {
            $el.classList.toggle('sticky', rect.bottom >= viewport);
            return;
        }

        // Only collapse when the page can still be scrolled to the stick point after the header
        // reclaims its height — otherwise collapsing would shorten the page below that point and flicker.
        const {reclaim, offset} = metrics.get($el) ?? {reclaim: 0, offset: 0};
        const stuck = $el.classList.contains('sticky');
        const hasRoom = maxScroll + (stuck ? reclaim : 0) - reclaim >= offset;

        $el.classList.toggle('sticky', hasRoom && rect.top <= 0);
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

    // Apply the initial state with the transition suppressed, so a page that loads already scrolled
    // doesn't animate the collapse on load; the reflow commits it before transitions are restored.
    const transition = $el.style.transition;
    $el.style.transition = 'none';
    update();
    $el.getBoundingClientRect();
    $el.style.transition = transition;
};
