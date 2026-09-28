import {beforeEach, describe, expect, it} from 'vitest';
import translationLayout from '../../../src/js/includes/translationLayout';

const render = (layout = 'inline', invalid = '') => {
    document.body.innerHTML = `<form data-translation-layout="${layout}">
        <div data-translation-toolbar>
            <button data-translation-language="en-US">English</button>
            <button data-translation-language="de">Deutsch</button>
            <button data-translation-toggle data-label-inline="One" data-label-tabs="All">One</button>
        </div>
        <div id="status"></div>
        <div id="name" data-language="en-US"><input></div>
        <div id="name-de" data-language="de"><input ${invalid}></div>
    </form>`;

    translationLayout(document.querySelector('form')!);
};

const hidden = (id: string) => document.getElementById(id)!.hidden;

describe('translationLayout', () => {
    beforeEach(() => window.localStorage.clear());

    it('shows every language inline by default', () => {
        render();

        expect(hidden('name')).toBe(false);
        expect(hidden('name-de')).toBe(false);
        expect(document.querySelector<HTMLElement>('[data-translation-language]')!.hidden).toBe(true);
    });

    it('shows one language at a time and keeps the rest of the form', () => {
        render('tabs');

        expect(hidden('name')).toBe(false);
        expect(hidden('name-de')).toBe(true);
        expect(hidden('status')).toBe(false);

        document.querySelector<HTMLElement>('[data-translation-language="de"]')!.click();

        expect(hidden('name')).toBe(true);
        expect(hidden('name-de')).toBe(false);
    });

    it('remembers the switch for the next form', () => {
        render();
        document.querySelector<HTMLElement>('[data-translation-toggle]')!.click();

        expect(hidden('name-de')).toBe(true);
        expect(document.querySelector('[data-translation-toggle]')!.textContent).toBe('All');

        render();

        expect(hidden('name-de')).toBe(true);
    });

    it('brings a language with a rejected field to the front', () => {
        render('tabs', 'aria-invalid="true"');

        expect(hidden('name')).toBe(true);
        expect(hidden('name-de')).toBe(false);
    });
});
