/**
 * Dragging is the only way to reorder a grid with a pointer, which leaves a keyboard out entirely. On a focused
 * handle, the up and down arrows move its row one place and the caller posts the new order, exactly as a drop
 * does. The new position is announced through a polite live region, worded by the server
 * (`data-sort-announcement`, holding `{position}` and `{count}`), since a script has no translations.
 */
export const moveRowByKey = (event: KeyboardEvent, $list: HTMLElement): boolean => {
    if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
        return false;
    }

    const $handle = (event.target as HTMLElement | null)?.closest<HTMLElement>('.sortable-handle');
    const $row = $handle ? [...$list.children].find(($child) => $child.contains($handle)) : undefined;

    if (!$handle || !$row) {
        return false;
    }

    const $sibling = event.key === 'ArrowUp' ? $row.previousElementSibling : $row.nextElementSibling;

    // The keys still belong to the page at either end, so the list does not swallow a scroll it cannot use.
    if (!$sibling) {
        return false;
    }

    event.preventDefault();

    if (event.key === 'ArrowUp') {
        $sibling.before($row);
    } else {
        $sibling.after($row);
    }

    // Moving a node takes the focus with it out of the document.
    $handle.focus();

    announce($list, [...$list.children].indexOf($row) + 1, $list.children.length);

    return true;
};

const announce = ($list: HTMLElement, position: number, count: number): void => {
    const template = $list.dataset.sortAnnouncement;

    if (!template) {
        return;
    }

    let $region = document.getElementById('sort-announcement');

    if (!$region) {
        $region = document.createElement('div');
        $region.id = 'sort-announcement';
        $region.className = 'visually-hidden';
        $region.setAttribute('aria-live', 'polite');
        document.body.append($region);
    }

    $region.textContent = template
        .replace('{position}', String(position))
        .replace('{count}', String(count));
};
