// A submenu is a horizontal scroller on a narrow screen, and every swap renders it scrolled back to the start —
// so the tab the page is on can sit off-screen, which is exactly where the user just clicked. Centre it instead,
// clamped by the scroller itself, so the first and last tabs stay where they are.
export default ($el: HTMLElement): void => {
    const $active = $el.querySelector('.active');

    if (!$active || $el.scrollWidth <= $el.clientWidth) {
        return;
    }

    const item = $active.getBoundingClientRect();
    const container = $el.getBoundingClientRect();

    $el.scrollLeft += item.left - container.left - (container.width - item.width) / 2;
};
