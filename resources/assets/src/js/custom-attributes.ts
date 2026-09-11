// @ts-ignore
import Sortable, {AutoScroll} from 'sortablejs/modular/sortable.core.esm.js';
import {SortableOptions} from "sortablejs";
import htmx from "htmx.org";

Sortable.mount(new AutoScroll());

htmx.onLoad(($node) => {
    if (!($node instanceof HTMLElement)) {
        return;
    }

    $node.querySelectorAll<HTMLElement>('[data-group]').forEach(($group) => {
        const $items = $group.querySelector<HTMLElement>('[data-group-items]');
        const $template = $group.querySelector<HTMLTemplateElement>('[data-group-template]');
        const $add = $group.querySelector<HTMLButtonElement>('[data-group-add]');

        if (!$items) {
            return;
        }

        const min = Number($group.dataset.groupMin || 0);
        const max = Number($group.dataset.groupMax || Infinity);

        const update = () => {
            const count = $items.children.length;

            if ($add) {
                $add.hidden = count >= max;
            }

            // The server renders the rows a minimum count demands, so they cannot be removed either.
            $items.querySelectorAll<HTMLElement>('[data-group-remove]').forEach(($remove) => {
                $remove.hidden = count <= min;
            });
        };

        $add?.addEventListener('click', () => {
            const index = String(Date.now());
            // The input names and the ids derived from them carry different placeholders.
            const html = ($template?.innerHTML ?? '')
                .replaceAll($group.dataset.groupPlaceholder!, index)
                .replaceAll($group.dataset.groupPlaceholderId!, index);

            $items.insertAdjacentHTML('beforeend', html);

            const $item = $items.lastElementChild as HTMLElement | null;

            if ($item) {
                // Wires `data-toggle`, tooltips and the `tinymce-editor` element of the inserted item.
                htmx.process($item);
                $item.querySelector<HTMLElement>('input,select,textarea')?.focus();
            }

            update();
        });

        $group.addEventListener('click', (event) => {
            const $remove = (event.target as HTMLElement).closest<HTMLElement>('[data-group-remove]');

            if ($remove && $group.contains($remove)) {
                $remove.closest('[data-group-item]')?.remove();
                update();
            }
        });

        if ($group.dataset.groupSortable !== undefined) {
            // The rows are reindexed from the request order on save, so a move needs no renumbering.
            new Sortable($items, {
                handle: '.sortable-handle',
                direction: 'vertical',
                animation: 150,
            } as SortableOptions);
        }

        update();
    });
})
