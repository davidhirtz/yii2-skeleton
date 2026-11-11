// htmx 4.0.1 cancels a modified click on a boosted link before it decides to leave it to the browser, so ⌘-, Ctrl-
// or Shift-clicking a link opened nothing (monorepo #780). Stopped on the document's capture phase, the click never
// reaches htmx's listener on the link and the browser opens it in a new tab or window.
document.addEventListener('click', (event: MouseEvent) => {
    if (event.button !== 0 || !(event.ctrlKey || event.metaKey || event.shiftKey || event.altKey)) {
        return;
    }

    const $link = event.target instanceof Element ? event.target.closest('a[href]') : null;

    if ($link && !$link.getAttribute('href')?.startsWith('#')) {
        event.stopPropagation();
    }
}, true);
