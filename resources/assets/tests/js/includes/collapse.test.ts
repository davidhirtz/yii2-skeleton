import {describe, expect, it} from 'vitest';
import collapse from '../../../src/js/includes/collapse';

describe('collapse', () => {
    it('keeps the state on every button toggling the target', () => {
        document.body.innerHTML = '<div id="card" class="card collapsed">'
            + '<button data-collapse="#card" aria-expanded="false">Title</button>'
            + '<button data-collapse="#card" aria-expanded="false">Toggle</button></div>';

        const $buttons = [...document.querySelectorAll<HTMLElement>('[data-collapse]')];
        $buttons.forEach(collapse);

        $buttons[1].click();
        expect(document.getElementById('card')!.classList.contains('collapsed')).toBe(false);
        expect($buttons.map(($button) => $button.getAttribute('aria-expanded'))).toEqual(['true', 'true']);
        expect(document.getElementById('card')!.hasAttribute('aria-expanded')).toBe(false);

        $buttons[0].click();
        expect($buttons.map(($button) => $button.getAttribute('aria-expanded'))).toEqual(['false', 'false']);
    });
});
