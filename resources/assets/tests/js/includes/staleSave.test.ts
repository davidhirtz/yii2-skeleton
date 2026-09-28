import {describe, expect, it} from 'vitest';
import staleSave from '../../../src/js/includes/staleSave';

const flash = (text: string) => `<flash-alert><div class="alert" data-alert="warning"><div class="alert-content">${text}</div></div></flash-alert>`;

describe('staleSave', () => {
    it('takes the warning away when the form is saved again', () => {
        document.body.innerHTML = `<div id="flashes">${flash('Changed by David.')}${flash('Something else.')}</div>`
            + '<form data-stale-save="Changed by David."></form>';

        staleSave(document.querySelector('form')!);
        document.querySelector('form')!.dispatchEvent(new Event('submit'));

        expect([...document.querySelectorAll('#flashes .alert-content')].map(($e) => $e.textContent)).toEqual(['Something else.']);
    });
});
