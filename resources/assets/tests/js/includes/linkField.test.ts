import {beforeEach, describe, expect, it, vi} from 'vitest';

import linkField from '../../../src/js/includes/linkField';

const render = (selected: boolean) => {
    document.body.innerHTML = `<div class="link-field" data-link-field>
        <div class="input-group link-field-selected" ${selected ? '' : 'hidden'}>
            <a class="input link-field-name" data-link-field-name href="/admin/old"><span data-link-field-text>Old</span><span data-link-field-description>/old</span></a>
            <div class="input-group-append"><button type="button" data-link-field-clear></button></div>
        </div>
        <div class="link-field-input" ${selected ? 'hidden' : ''}>
            <div class="autocomplete" data-autocomplete><input name="Model[link]" value="${selected ? 'entry:1' : ''}"></div>
        </div>
    </div>`;

    linkField(document.querySelector<HTMLElement>('[data-link-field]')!);
};

const $ = <T extends HTMLElement>(selector: string) => document.querySelector<T>(selector)!;

describe('linkField', () => {
    beforeEach(() => render(false));

    it('shows a pick by its name, the input staying in the form', () => {
        $('[data-autocomplete]').dispatchEvent(new CustomEvent('autocomplete:select', {
            bubbles: true,
            detail: {value: 'entry:12', text: 'Ångström 東京 🚀', description: '/karriere', url: '/admin/cms/entry/update?id=12'},
        }));

        expect($('.link-field-selected').hidden).toBe(false);
        expect($('.link-field-input').hidden).toBe(true);
        expect($('[data-link-field-text]').textContent).toBe('Ångström 東京 🚀');
        expect($('[data-link-field-description]').textContent).toBe('/karriere');
        expect($<HTMLAnchorElement>('[data-link-field-name]').getAttribute('href')).toBe('/admin/cms/entry/update?id=12');
    });

    it('drops the href of a pick the user may not open', () => {
        $('[data-autocomplete]').dispatchEvent(new CustomEvent('autocomplete:select', {
            bubbles: true,
            detail: {value: 'entry:12', text: 'Home', description: '/'},
        }));

        expect($('[data-link-field-name]').hasAttribute('href')).toBe(false);
    });

    it('clears back to an empty input and tells the form', () => {
        render(true);
        const changed = vi.fn();
        $('input').addEventListener('change', changed);

        $('[data-link-field-clear]').click();

        expect($<HTMLInputElement>('input').value).toBe('');
        expect($('.link-field-input').hidden).toBe(false);
        expect($('.link-field-selected').hidden).toBe(true);
        expect(changed).toHaveBeenCalled();
    });
});
