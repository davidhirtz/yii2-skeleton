import htmx from 'htmx.org';

import sortableOrder from './sortableOrder';

/**
 * Each move posts the whole order. htmx's default sync keeps one request in flight and one queued and drops the
 * rest, so a third arrow press within a round trip never reached the server; `queue last` keeps the newest.
 */
export default ($el: HTMLElement) => {
    $el.setAttribute('hx-sync', 'queue last');

    // Let htmx issue the request so it inherits the CSRF header from #wrap
    // and processes the out-of-band flash messages returned by the action.
    return htmx.ajax('POST', $el.dataset.sortUrl!, {
        source: $el,
        target: $el,
        swap: 'none',
        values: sortableOrder($el),
    });
};
