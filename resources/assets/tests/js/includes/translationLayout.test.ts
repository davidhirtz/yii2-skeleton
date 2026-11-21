import {beforeEach, describe, expect, it, vi} from 'vitest';
import translationLayout, {initTranslationTabs} from '../../../src/js/includes/translationLayout';

const render = (layout = 'inline', invalid = '', content = '') => {
    document.body.innerHTML = `<form data-translation-layout="${layout}">
        <ul data-translation-toolbar${content ? ` data-content-language="${content}"` : ''}>
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

    it('starts on the content language where the project says so', () => {
        render('tabs', '', 'de');

        expect(hidden('name')).toBe(true);
        expect(active()).toEqual(['DE']);
    });

    it('remembers a language tab for the next form, which opens on the content language', () => {
        render('inline', '', 'en-US');
        click('[data-translation-language="de"]');

        render('inline', '', 'en-US');

        expect(hidden('name')).toBe(false);
        expect(hidden('name-de')).toBe(true);
        expect(active()).toEqual(['EN']);

        render('inline', '', 'de');

        expect(active()).toEqual(['DE']);
    });

    it('remembers every field for the next form', () => {
        render('tabs', '', 'de');
        click('[data-translation-all]');

        render('tabs', '', 'de');

        expect(active()).toEqual(['All fields']);
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

    it('shows every field when the browser rejects one that does not show', () => {
        render('tabs');
        expect(hidden('name-de')).toBe(true);

        document.querySelector('#name-de input')!.dispatchEvent(new Event('invalid', {cancelable: true}));

        expect(hidden('name-de')).toBe(false);
        expect(active()).toEqual(['All fields']);
    });

    it('keeps the language when its own field was rejected', () => {
        window.localStorage.setItem('translationLanguage', 'de');
        render('inline', 'aria-invalid="true"', 'de');

        expect(active()).toEqual(['DE']);
    });

    describe('with more than two languages', () => {
        const renderThree = (content = 'en-US', layout = 'tabs', invalid = '') => {
            document.body.innerHTML = `<form data-translation-layout="${layout}">
                <ul data-translation-toolbar data-translation-multiple data-content-language="${content}">
                    <li><button data-translation-all>All fields</button></li>
                    <li><button data-translation-language="en-US">EN</button></li>
                    <li><button data-translation-language="de">DE</button></li>
                    <li><button data-translation-language="fr">FR</button></li>
                </ul>
                <div id="name" data-language="en-US"><input></div>
                <div id="name-de" data-language="de"><input></div>
                <div id="name-fr" data-language="fr"><input ${invalid}></div>
            </form>`;

            translationLayout(document.querySelector('form')!);
        };

        it('adds and removes a language with a click', () => {
            renderThree();
            expect(active()).toEqual(['EN']);

            click('[data-translation-language="de"]');

            expect(active()).toEqual(['EN', 'DE']);
            expect(hidden('name')).toBe(false);
            expect(hidden('name-de')).toBe(false);
            expect(hidden('name-fr')).toBe(true);
            expect(document.querySelector('form')!.dataset.translationChoice).toBe('en-US de');

            click('[data-translation-language="en-US"]');

            expect(active()).toEqual(['DE']);
            expect(hidden('name')).toBe(true);
        });

        it('shows every field when the last language is left out or every one is picked', () => {
            renderThree();
            click('[data-translation-language="en-US"]');

            expect(active()).toEqual(['All fields']);

            click('[data-translation-language="fr"]');
            expect(active()).toEqual(['FR']);

            click('[data-translation-language="de"]');
            click('[data-translation-language="en-US"]');

            expect(active()).toEqual(['All fields']);
            expect(hidden('name-fr')).toBe(false);
        });

        it('resets the selection with "All fields"', () => {
            renderThree();
            click('[data-translation-language="de"]');
            click('[data-translation-all]');

            expect(active()).toEqual(['All fields']);
            expect(hidden('name-de')).toBe(false);
            expect(hidden('name-fr')).toBe(false);
        });

        it('remembers a selection, as far as the next form offers it', () => {
            renderThree();
            click('[data-translation-language="fr"]');

            renderThree('de');
            expect(active()).toEqual(['EN', 'FR']);

            // A single remembered language opens on the content language, as with two.
            window.localStorage.setItem('translationLanguage', 'fr');
            renderThree('de');
            expect(active()).toEqual(['DE']);

            // Two languages show tabs only: a remembered selection opens on the content language.
            window.localStorage.setItem('translationLanguage', 'en-US,de');
            render('inline', '', 'de');
            expect(active()).toEqual(['DE']);
        });

        it('shows every field while a rejected one is not selected', () => {
            window.localStorage.setItem('translationLanguage', 'en-US,de');
            renderThree('en-US', 'tabs', 'aria-invalid="true"');

            expect(active()).toEqual(['All fields']);
        });
    });
});
