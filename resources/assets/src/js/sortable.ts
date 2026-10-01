import {SortableOptions} from "sortablejs";

import onLoad from './includes/onLoad';
import Sortable from './includes/sortable';
import {moveRowByKey} from './includes/sortableKeyboard';
import postOrder from './includes/postOrder';

onLoad(($node) => {
    ($node.querySelectorAll('[data-sort-url]') as NodeListOf<HTMLTableElement>).forEach(($el) => {
        new Sortable($el, {
            handle: '.sortable-handle',
            direction: 'vertical',
            onEnd: () => void postOrder($el),
        } as SortableOptions);

        $el.addEventListener('keydown', (event: KeyboardEvent) => {
            if (moveRowByKey(event, $el)) {
                void postOrder($el);
            }
        });
    });
})
