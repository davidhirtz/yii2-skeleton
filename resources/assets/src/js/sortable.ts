// @ts-ignore
import Sortable, {AutoScroll} from 'sortablejs/modular/sortable.core.esm.js';
import {SortableEvent, SortableOptions} from "sortablejs";

Sortable.mount(new AutoScroll());

import htmx from "htmx.org";

htmx.onLoad(($node) => {
    if (!($node instanceof HTMLElement)) {
        return;
    }

    ($node.querySelectorAll('[data-sort-url]') as NodeListOf<HTMLTableElement>).forEach(($el) => {
        new Sortable($el, {
            handle: '.sortable-handle',
            direction: 'vertical',
            onEnd: (evt: SortableEvent) => {
                const values: Record<string, string[]> = {};

                // @ts-ignore
                [...(evt.to.children as HTMLCollection<HTMLElement>)].forEach(($row) => {
                    // Row ids are `${camel2id(formName)}-${primaryKey}`, so the model name may itself
                    // contain dashes (e.g. `hotspot-asset-5`); the primary key is the last segment.
                    const parts = $row.id.split('-');
                    const id = parts.pop()!;
                    (values[`${parts.join('-')}[]`] ??= []).push(id);
                });

                // Let htmx issue the request so it inherits the CSRF header from #wrap
                // and processes the out-of-band flash messages returned by the action.
                void htmx.ajax('POST', $el.dataset.sortUrl!, {
                    source: $el,
                    target: $el,
                    swap: 'none',
                    values,
                });
            },
        } as SortableOptions);
    });
})