import {beforeEach, describe, expect, it} from 'vitest';
import {focusFirstInvalid} from '../../../src/js/includes/focus';

describe('focusFirstInvalid', () => {
    beforeEach(() => {
        document.body.innerHTML = '<div id="wrap"><input id="name"><input id="email" aria-invalid="true">'
            + '<input id="slug" aria-invalid="true"></div>';
    });

    it('focuses the first rejected field after a save', () => {
        focusFirstInvalid('POST');
        expect(document.activeElement?.id).toBe('email');
    });

    it('leaves the focus alone after a navigation', () => {
        focusFirstInvalid('GET');
        expect(document.activeElement).toBe(document.body);
    });
});
