import {beforeEach, describe, expect, it} from 'vitest';
import {focusFirstInvalid, rememberFocus, restoreFocus} from '../../../src/js/includes/focus';

const page = '<div id="wrap"><h1>Entries</h1><select id="entry-type"></select><input id="name">'
    + '<input id="email" aria-invalid="true"></div>';

describe('focus', () => {
    beforeEach(() => {
        document.body.innerHTML = page.replace(' aria-invalid="true"', '');
    });

    it('focuses the first rejected field after a save', () => {
        document.body.innerHTML = page;

        expect(focusFirstInvalid('POST')).toBe(true);
        expect(document.activeElement?.id).toBe('email');
    });

    it('leaves the invalid fields alone after a navigation', () => {
        document.body.innerHTML = page;

        expect(focusFirstInvalid('GET')).toBe(false);
        expect(document.activeElement).toBe(document.body);
    });

    it('returns to the control that reloaded the form', () => {
        document.getElementById('entry-type')!.focus();
        rememberFocus();

        document.body.innerHTML = page.replace(' aria-invalid="true"', '');
        restoreFocus({target: document.getElementById('wrap'), request: {method: 'POST', headers: {'X-Form-Reload': '1'}}});

        expect(document.activeElement?.id).toBe('entry-type');
    });

    it('focuses the heading after a navigation', () => {
        document.getElementById('name')!.focus();
        rememberFocus();

        document.body.innerHTML = page.replace(' aria-invalid="true"', '');
        restoreFocus({target: document.getElementById('wrap'), request: {method: 'GET', headers: {}}});

        expect(document.activeElement?.tagName).toBe('H1');
    });

    it('keeps the control after a narrower swap', () => {
        document.getElementById('name')!.focus();
        rememberFocus();

        document.body.innerHTML = page.replace(' aria-invalid="true"', '');
        restoreFocus({target: document.createElement('div'), request: {method: 'GET', headers: {}}});

        expect(document.activeElement?.id).toBe('name');
    });
});
