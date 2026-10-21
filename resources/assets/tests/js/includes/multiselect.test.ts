import {beforeEach, describe, expect, it, vi} from 'vitest';

// happy-dom has no popover API, so the open state is a flag on the element.
vi.mock('../../../src/js/includes/popover', () => ({
    openUnder: (_$anchor: HTMLElement, $popover: HTMLElement) => {
        $popover.dataset.open = 'true';
        return () => undefined;
    },
}));

import multiselect from '../../../src/js/includes/multiselect';

describe('multiselect', () => {
    let $select: HTMLSelectElement;
    let $input: HTMLInputElement;
    let $popover: HTMLElement;

    beforeEach(() => {
        document.body.innerHTML = '<form><label for="languages">Languages</label>'
            + '<select id="languages" name="languages[]" multiple data-multiselect="Remove {label}">'
            + '<option value="en-US" selected disabled>English</option>'
            + '<option value="de" selected>Deutsch</option>'
            + '<option value="fr">Français</option>'
            + '<option value="it" selected data-locked>Italiano</option>'
            + '<option value="pl">Polski</option>'
            + '</select></form>';

        $select = document.querySelector('select')!;
        multiselect($select);

        $input = document.querySelector('.multiselect-search')!;
        $popover = document.querySelector('.multiselect-results')!;

        const matches = $popover.matches.bind($popover);
        vi.spyOn($popover, 'matches').mockImplementation((selector: string) => selector === ':popover-open'
            ? $popover.dataset.open === 'true'
            : matches(selector));
        $popover.hidePopover = () => delete $popover.dataset.open;
    });

    const tags = () => [...document.querySelectorAll('.multiselect-tag')].map(($tag) => $tag.firstChild?.textContent);
    const offered = () => [...$popover.querySelectorAll('[role="option"]')].map(($option) => $option.textContent);
    const selected = () => [...$select.selectedOptions].map(($option) => $option.value);

    const press = (key: string) => {
        const event = new KeyboardEvent('keydown', {key, bubbles: true, cancelable: true});
        $input.dispatchEvent(event);
        return event;
    };

    const type = (text: string) => {
        $input.value = text;
        $input.dispatchEvent(new Event('input'));
    };

    it('hides the select and shows the selected options as tags', () => {
        expect($select.hidden).toBe(true);
        expect(tags()).toEqual(['English', 'Deutsch', 'Italiano']);
        expect($input.getAttribute('aria-labelledby')).toBe('languages-label');
    });

    it('offers no remove button for a disabled or a locked option', () => {
        const $buttons = document.querySelectorAll('.multiselect-remove');

        expect($buttons).toHaveLength(1);
        expect($buttons[0].getAttribute('aria-label')).toBe('Remove Deutsch');
    });

    it('offers only the options not yet selected, filtered without accents', () => {
        type('francais');

        expect(offered()).toEqual(['Français']);
        expect($input.getAttribute('aria-expanded')).toBe('true');
    });

    it('selects the active option on Enter without submitting the form', () => {
        const change = vi.fn();
        $select.addEventListener('change', change);

        press('ArrowDown');
        expect(press('Enter').defaultPrevented).toBe(true);

        expect(selected()).toEqual(['en-US', 'de', 'fr', 'it']);
        expect(tags()).toEqual(['English', 'Deutsch', 'Français', 'Italiano']);
        expect(offered()).toEqual(['Polski']);
        expect(change).toHaveBeenCalledOnce();
    });

    it('removes a tag through its button', () => {
        (document.querySelector('.multiselect-remove') as HTMLButtonElement).click();

        expect(selected()).toEqual(['en-US', 'it']);
        expect(tags()).toEqual(['English', 'Italiano']);
    });

    it('removes the last removable tag on Backspace in an empty input', () => {
        expect(press('Backspace').defaultPrevented).toBe(true);
        expect(selected()).toEqual(['en-US', 'it']);

        expect(press('Backspace').defaultPrevented).toBe(false);
        expect(selected()).toEqual(['en-US', 'it']);
    });

    it('collapses on Escape', () => {
        press('ArrowDown');
        press('Escape');

        expect($input.getAttribute('aria-expanded')).toBe('false');
        expect($popover.dataset.open).toBeUndefined();
    });

    it('is initialised once', () => {
        multiselect($select);

        expect(document.querySelectorAll('.multiselect')).toHaveLength(1);
    });
});
