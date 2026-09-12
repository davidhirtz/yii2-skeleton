const teardowns = new Map<Element, () => void>();

const observer = new MutationObserver(() => {
    teardowns.forEach((teardown, $el) => {
        if (!$el.isConnected) {
            teardowns.delete($el);
            teardown();
        }
    });

    teardowns.size || observer.disconnect();
});

// A popover removed from the DOM while open is hidden without firing `toggle`, so whatever it set up on open is
// only torn down by watching whether the element is still connected. The returned handle runs the teardown right
// away and is a no-op once it ran, so the regular close path and a removal share one exit.
export const teardownOnDisconnect = ($el: Element, teardown: () => void) => {
    teardowns.set($el, teardown);
    observer.observe(document.documentElement, {childList: true, subtree: true});

    return () => {
        if (teardowns.delete($el)) {
            teardown();
        }

        teardowns.size || observer.disconnect();
    };
}
