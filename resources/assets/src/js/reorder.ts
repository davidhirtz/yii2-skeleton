import {SortableOptions} from "sortablejs";

import onLoad from './includes/onLoad';
import Sortable from './includes/sortable';
import {moveRowByKey} from './includes/reorderKeyboard';
import postReorder from './includes/postReorder';

onLoad(($node) => {
    ($node.querySelectorAll('[data-reorder-url]') as NodeListOf<HTMLTableElement>).forEach(($el) => {
        new Sortable($el, {
            handle: '.reorder-handle',
            direction: 'vertical',
            onEnd: () => void postReorder($el),
        } as SortableOptions);

        $el.addEventListener('keydown', (event: KeyboardEvent) => {
            if (moveRowByKey(event, $el)) {
                void postReorder($el);
            }
        });
    });
})
