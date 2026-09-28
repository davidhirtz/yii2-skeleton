import {beforeEach, describe, expect, it} from 'vitest';
import networkError from '../../../src/js/includes/networkError';

const dispatch = (detail: object) => networkError(new CustomEvent('htmx:error', {detail}));

describe('networkError', () => {
    beforeEach(() => {
        document.body.innerHTML = '<div id="flashes"></div>'
            + '<template id="network-error-flash"><div class="alert">Not saved</div></template>';
    });

    it('flashes a request that got no response', () => {
        dispatch({ctx: {}, error: new TypeError('Failed to fetch')});

        expect(document.querySelector('#flashes .alert')?.textContent).toBe('Not saved');
    });

    it('leaves a request that got a response to the error modal', () => {
        dispatch({ctx: {response: {status: 500}}, error: new TypeError()});

        expect(document.querySelector('#flashes .alert')).toBeNull();
    });

    it('ignores a request htmx aborted itself', () => {
        dispatch({ctx: {}, error: new DOMException('Aborted', 'AbortError')});

        expect(document.querySelector('#flashes .alert')).toBeNull();
    });
});
