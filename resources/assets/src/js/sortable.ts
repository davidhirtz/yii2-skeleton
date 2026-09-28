import {SortableOptions} from "sortablejs";
import htmx from "htmx.org";

import onLoad from './includes/onLoad';
import Sortable from './includes/sortable';
import {moveRowByKey} from './includes/sortableKeyboard';
import sortableOrder from './includes/sortableOrder';

const postOrder = ($el: HTMLElement) => {
    // Let htmx issue the request so it inherits the CSRF header from #wrap
    // and processes the out-of-band flash messages returned by the action.
    void htmx.ajax('POST', $el.dataset.sortUrl!, {
        source: $el,
        target: $el,
        swap: 'none',
        values: sortableOrder($el),
    });
};

onLoad(($node) => {
    ($node.querySelectorAll('[data-sort-url]') as NodeListOf<HTMLTableElement>).forEach(($el) => {
        new Sortable($el, {
            handle: '.sortable-handle',
            direction: 'vertical',
            onEnd: () => postOrder($el),
        } as SortableOptions);

        $el.addEventListener('keydown', (event: KeyboardEvent) => {
            if (moveRowByKey(event, $el)) {
                postOrder($el);
            }
        });
    });
})
