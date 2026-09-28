import {describe, expect, it} from 'vitest';
import characterCounter from '../../../src/js/includes/characterCounter';

describe('characterCounter', () => {
    it('counts the characters typed into the field', () => {
        document.body.innerHTML = '<textarea id="d" aria-describedby="d-hint" data-character-counter>Hi</textarea>'
            + '<div id="d-hint">Now: <span data-character-count>2</span>.</div>';

        const $input = document.querySelector('textarea')!;
        characterCounter($input);

        $input.value = 'Grüße 👋';
        $input.dispatchEvent(new Event('input'));

        expect(document.querySelector('[data-character-count]')!.textContent).toBe('7');
    });
});
