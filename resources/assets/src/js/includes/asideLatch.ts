const OPEN = 'data-aside-open';

/**
 * A collapsed aside opens on hover, which is gone the moment the pointer leaves — so a click inside it latches
 * it open until the next click outside, which is what makes a submenu item reachable. The attribute is on
 * `<html>`, outside `#wrap`, so the swap the click triggers leaves it standing.
 *
 * The listener is registered once, at module scope: `onLoad` runs again for every swap and would otherwise
 * stack a copy per navigation.
 */
document.addEventListener('click', (event: MouseEvent) => {
    const $target = event.target instanceof Element ? event.target : null;

    // The pin button is inside the aside but is the one control that means "stop showing this", so a click on
    // it must not latch what it just collapsed.
    const latch = !!$target?.closest('#aside') && !$target.closest('[data-aside-pin]');

    document.documentElement.toggleAttribute(OPEN, latch);
});

document.addEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        document.documentElement.removeAttribute(OPEN);
    }
});
