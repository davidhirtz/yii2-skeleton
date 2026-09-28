import {beforeEach, describe, expect, it} from 'vitest';
import {confirmLeaving, hasUnsavedChanges, resetUnsavedForms, trackUnsavedForm} from '../../../src/js/includes/unsaved';

const render = (attributes = '') => {
    document.body.innerHTML = `<div id="wrap"><form data-unsaved="Leave?" ${attributes}><input name="name"></form>`
        + '<a id="elsewhere"></a></div>';

    const $form = document.querySelector('form')!;
    trackUnsavedForm($form);

    return $form;
};

const navigate = (confirm: (message: string) => boolean, source?: Element | null) => confirmLeaving({
    sourceElement: source ?? document.getElementById('elsewhere'),
    target: document.getElementById('wrap'),
    request: {method: 'GET'},
}, confirm);

describe('unsaved', () => {
    beforeEach(() => resetUnsavedForms());

    it('lets a clean form go without asking', () => {
        render();

        expect(navigate(() => {
            throw new Error('asked');
        })).toBe(true);
    });

    it('asks before a navigation leaves a changed form', () => {
        const $form = render();
        $form.querySelector('input')!.dispatchEvent(new Event('input', {bubbles: true}));

        let asked = '';
        expect(navigate((message) => {
            asked = message;
            return false;
        })).toBe(false);
        expect(asked).toBe('Leave?');
        expect(hasUnsavedChanges()).toBe(true);

        expect(navigate(() => true)).toBe(true);
        expect(hasUnsavedChanges()).toBe(false);
    });

    it('treats a form the server marked as changed from the start', () => {
        render('data-dirty');

        expect(hasUnsavedChanges()).toBe(true);
    });

    it('lets the form submit and reload itself', () => {
        const $form = render('data-dirty');

        expect(navigate(() => false, $form.querySelector('input'))).toBe(true);

        $form.dispatchEvent(new Event('submit'));
        expect(hasUnsavedChanges()).toBe(false);
    });

    it('ignores a swap narrower than the page and any post', () => {
        render('data-dirty');

        expect(confirmLeaving({target: document.createElement('div'), request: {method: 'GET'}}, () => false)).toBe(true);
        expect(confirmLeaving({target: document.getElementById('wrap'), request: {method: 'POST'}}, () => false)).toBe(true);
    });
});
