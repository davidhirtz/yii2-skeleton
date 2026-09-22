import {SortableOptions} from "sortablejs";
import htmx from "htmx.org";

import onLoad from './includes/onLoad';
import Sortable from './includes/sortable';

// Matches `Widgets\Forms\Fields\GroupField::POSITION_PLACEHOLDER`.
const POSITION_PLACEHOLDER = '__POSITION__';

onLoad(($node) => {
    $node.querySelectorAll<HTMLElement>('[data-group]').forEach(($group) => {
        const $items = $group.querySelector<HTMLElement>('[data-group-items]');
        const $template = $group.querySelector<HTMLTemplateElement>('[data-group-template]');
        const $add = $group.querySelector<HTMLButtonElement>('[data-group-add]');

        if (!$items) {
            return;
        }

        const min = Number($group.dataset.groupMin || 0);
        const max = Number($group.dataset.groupMax || Infinity);
        const position = $group.dataset.groupPosition ?? '';

        const getRows = () => Array.from($items.children) as HTMLElement[];

        // What a collapsed row is previewed by: the value as it reads on screen, so a select is its option
        // rather than its value. A checkbox says nothing about the row, so it counts as nothing typed.
        const getTitle = ($row: HTMLElement): string => {
            const $input = $row.querySelector<HTMLElement>('[data-group-title-input]');

            if ($input instanceof HTMLSelectElement) {
                return $input.selectedOptions[0]?.textContent?.trim() ?? '';
            }

            if ($input instanceof HTMLTextAreaElement) {
                return $input.value.trim();
            }

            return $input instanceof HTMLInputElement && !['checkbox', 'radio'].includes($input.type)
                ? $input.value.trim()
                : '';
        };

        const setTitle = ($row: HTMLElement, index: number) => {
            const $title = $row.querySelector<HTMLElement>('[data-group-title]');

            if (!$title) {
                return;
            }

            const title = getTitle($row);

            $title.toggleAttribute('data-group-title-position', title === '');
            $title.textContent = title || position.replace(POSITION_PLACEHOLDER, String(index + 1));
        };

        // Only the rows showing their number, the rest being what the user typed — and the number every one of
        // them shows is wrong the moment a row is added, removed or moved.
        const renumber = () => {
            getRows().forEach(($row, index) => {
                const $title = $row.querySelector<HTMLElement>('[data-group-title][data-group-title-position]');

                if ($title) {
                    $title.textContent = position.replace(POSITION_PLACEHOLDER, String(index + 1));
                }
            });
        };

        const expand = ($row: HTMLElement, expanded: boolean) => {
            const $body = $row.querySelector<HTMLElement>('[data-group-body]');

            if ($body) {
                $body.hidden = !expanded;
                $row.querySelector('[data-group-toggle]')?.setAttribute('aria-expanded', String(expanded));
            }
        };

        const update = () => {
            const count = $items.children.length;

            if ($add) {
                $add.hidden = count >= max;
            }

            // The server renders the rows a minimum count demands, so they cannot be removed either, and one
            // row has nowhere to move to.
            $items.querySelectorAll<HTMLElement>('[data-group-remove]').forEach(($remove) => {
                $remove.hidden = count <= min;
            });

            $items.querySelectorAll<HTMLElement>('.sortable-handle').forEach(($handle) => {
                $handle.hidden = count < 2;
            });

            renumber();
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
                // Wires the tooltips and the `tinymce-editor` element of the inserted item.
                htmx.process($item);
                $item.querySelector<HTMLElement>('input,select,textarea')?.focus();
            }

            update();
        });

        $group.addEventListener('click', (event) => {
            const $target = event.target as HTMLElement;

            const $remove = $target.closest<HTMLElement>('[data-group-remove]');

            if ($remove && $group.contains($remove)) {
                $remove.closest('[data-group-item]')?.remove();
                update();
                return;
            }

            const $toggle = $target.closest<HTMLElement>('[data-group-toggle]');
            const $row = $toggle && $group.contains($toggle) ? $toggle.closest<HTMLElement>('[data-group-item]') : null;
            const $body = $row?.querySelector<HTMLElement>('[data-group-body]');

            if ($row && $body) {
                expand($row, Boolean($body.hidden));
            }
        });

        $group.addEventListener('input', (event) => {
            const $row = (event.target as HTMLElement).closest<HTMLElement>('[data-group-item]');

            if ($row) {
                setTitle($row, getRows().indexOf($row));
            }
        });

        // A control the browser cannot focus blocks the submit with nothing on screen to say why, so the row
        // holding one opens before the browser goes looking for it. `invalid` does not bubble, hence the capture.
        $group.addEventListener('invalid', (event) => {
            const $row = (event.target as HTMLElement).closest<HTMLElement>('[data-group-item]');

            if ($row?.querySelector<HTMLElement>('[data-group-body]')?.hidden) {
                expand($row, true);
            }
        }, true);

        if ($group.dataset.groupSortable !== undefined) {
            // The input names are reindexed from the request order on save, so only the numbers on screen
            // have to follow a move.
            new Sortable($items, {
                handle: '.sortable-handle',
                direction: 'vertical',
                animation: 150,
                onEnd: renumber,
            } as SortableOptions);
        }

        update();
    });
})
