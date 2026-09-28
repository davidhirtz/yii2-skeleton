import {beforeEach, describe, expect, it, vi} from 'vitest';
import translationLayout, {initTranslationTabs} from '../../../src/js/includes/translationLayout';

const render = (layout = 'inline', invalid = '') => {
    document.body.innerHTML = `<form data-translation-layout="${layout}">
        <ul data-translation-toolbar>
            <li><button data-translation-all>All fields</button></li>
            <li><button data-translation-language="en-US">EN</button></li>
            <li><button data-translation-language="de">DE</button></li>
        </ul>
        <div id="status"></div>
        <div id="name" data-language="en-US"><input></div>
        <div id="name-de" data-language="de"><input ${invalid}></div>
    </form>`;

    translationLayout(document.querySelector('form')!);
};

const hidden = (id: string) => document.getElementById(id)!.hidden;
const active = () => [...document.querySelectorAll('.active')].map(($button) => $button.textContent);
const click = (selector: string) => document.querySelector<HTMLElement>(selector)!.click();

describe('translationLayout', () => {
    beforeEach(() => window.localStorage.clear());

    it('shows every field by default', () => {
        render();

        expect(hidden('name')).toBe(false);
        expect(hidden('name-de')).toBe(false);
        expect(active()).toEqual(['All fields']);
    });

    it('starts on the first language where the project says so', () => {
        render('tabs');

        expect(hidden('name')).toBe(false);
        expect(hidden('name-de')).toBe(true);
        expect(active()).toEqual(['EN']);
    });

    it('shows one language at a time and keeps the rest of the form', () => {
        render();
        click('[data-translation-language="de"]');

        expect(hidden('name')).toBe(true);
        expect(hidden('name-de')).toBe(false);
        expect(hidden('status')).toBe(false);
        expect(active()).toEqual(['DE']);

        click('[data-translation-all]');

        expect(hidden('name')).toBe(false);
    });

    it('remembers the choice for the next form', () => {
        render();
        click('[data-translation-language="de"]');

        render();

        expect(hidden('name')).toBe(true);
        expect(active()).toEqual(['DE']);
    });

    it('finds tabs placed before the form, and tells the styles what shows', () => {
        document.body.innerHTML = `<ul data-translation-toolbar="entry">
                <li><button data-translation-all>All fields</button></li>
                <li><button data-translation-language="de">DE</button></li>
            </ul>
            <form id="entry"><div id="name-de" data-language="de"><input></div><div id="name" data-language="en-US"></div></form>`;

        const $form = document.querySelector('form')!;
        translationLayout($form);

        expect($form.dataset.translationChoice).toBe('all');

        click('[data-translation-language="de"]');

        expect($form.dataset.translationChoice).toBe('de');
        expect(hidden('name')).toBe(true);
    });

    it('shows every field again while a rejected one is in another language', () => {
        render('tabs', 'aria-invalid="true"');

        expect(hidden('name')).toBe(false);
        expect(hidden('name-de')).toBe(false);
        expect(active()).toEqual(['All fields']);

        // Not remembered: the next form opens on the editor's choice again.
        render('tabs');
        expect(active()).toEqual(['EN']);
    });

    it('binds tabs swapped in after their form, once', () => {
        document.body.innerHTML = `<ul data-translation-toolbar="entry">
                <li><button data-translation-all>All fields</button></li>
                <li><button data-translation-language="de">DE</button></li>
            </ul>
            <form id="entry" data-translation-layout="tabs"><div id="name-de" data-language="de"></div><div id="name" data-language="en-US"></div></form>`;

        const $toolbar = document.querySelector<HTMLElement>('[data-translation-toolbar]')!;
        initTranslationTabs($toolbar);
        translationLayout(document.querySelector('form')!);

        expect(active()).toEqual(['DE']);

        const setItem = vi.spyOn(window.localStorage, 'setItem');
        click('[data-translation-all]');

        expect(setItem).toHaveBeenCalledTimes(1);
        setItem.mockRestore();
    });

    it('keeps the language when its own field was rejected', () => {
        window.localStorage.setItem('translationLanguage', 'de');
        render('inline', 'aria-invalid="true"');

        expect(active()).toEqual(['DE']);
    });
});
