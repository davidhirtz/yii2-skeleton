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
                // @ts-ignore
                const data = [...(evt.to.children as HTMLCollection<HTMLElement>)]
                    .map(($el) => {
                        const values = $el.id.split('-');
                        return `${values[0]}[]=${values[1]}`;
                    })
                    .join('&');

                void fetch($el.dataset.sortUrl!, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')!.getAttribute('content'),
                    } as HeadersInit,
                    body: data,
                });
            },
        } as SortableOptions);
    });
})