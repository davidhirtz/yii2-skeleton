import {afterEach, describe, expect, it} from 'vitest';
import busy from '../../../src/js/includes/busy';

const dispatch = ($el: HTMLElement, name: string, ctx: object) => {
    $el.dispatchEvent(new CustomEvent(name, {bubbles: true, detail: {ctx}}));
};

describe('busy', () => {
    afterEach(() => {
        document.body.inert = false;
        document.body.innerHTML = '';
    });

    it('lifts the overlay when a request fails without a response', () => {
        const $el = document.createElement('form');
        document.body.append($el);
        busy($el);

        const ctx = {};
        dispatch($el, 'htmx:before:request', ctx);
        expect(document.body.inert).toBe(true);

        // A rejected fetch goes to `htmx:error` and `htmx:finally:request`, never `htmx:after:request`.
        dispatch($el, 'htmx:error', ctx);
        dispatch($el, 'htmx:finally:request', ctx);
        expect(document.body.inert).toBe(false);
    });

    it('ignores a request that never started', () => {
        const $el = document.createElement('form');
        document.body.append($el);
        busy($el);

        const ctx = {};
        dispatch($el, 'htmx:before:request', ctx);
        dispatch($el, 'htmx:finally:request', {});
        expect(document.body.inert).toBe(true);

        dispatch($el, 'htmx:finally:request', ctx);
        expect(document.body.inert).toBe(false);
    });
});
