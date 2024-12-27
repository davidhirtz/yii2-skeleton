import Sortable, {AutoScroll} from 'sortablejs/modular/sortable.core.esm.js';
import {SortableEvent, SortableOptions} from "sortablejs";

Sortable.mount(new AutoScroll());

export default (selector: string) => {
    const $el = document.querySelector(selector) as HTMLTableElement;

    if ($el) {
        new Sortable($el, {
            handle: '.sortable-handle',
            direction: 'vertical',
            onEnd: (evt: SortableEvent) => {
                // @ts-ignore
                const data = [...evt.to.children]
                    .map((el: HTMLElement) => {
                        const values = el.id.split('-');
                        return `${values[0]}[]=${values[1]}`;
                    })
                    .join('&');

                void fetch($el.dataset.sortUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: data,
                });
            },
        } as SortableOptions);
    }
}