import "htmx.org"
import Sortable, { AutoScroll } from 'sortablejs/modular/sortable.core.esm.js';
import {SortableEvent} from "sortablejs";
Sortable.mount(new AutoScroll());



const scroll = ($el: HTMLElement) => {
    new Sortable($el, {
            handle: '.handle',
            onEnd: (evt: SortableEvent) => {
                console.log(evt.to);
                // const $item = evt.item;
                // const $items = $item.parentNode.querySelectorAll('.sortable-item');
                // const ids = Array.from($items).map($item => $item.dataset.id);
                // const url = $sortable.dataset.url;
                // fetch(url, {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json',
                //     },
                //     body: JSON.stringify(ids),
                // });
            },
        });
}

scroll(document.getElementById('sortable') as HTMLElement);