import {beforeEach, describe, expect, it} from 'vitest';
import combobox from '../../../src/js/includes/combobox';

describe('combobox', () => {
    let $input: HTMLInputElement;
    let $listbox: HTMLElement;

    beforeEach(() => {
        document.body.innerHTML = '<input role="combobox" aria-expanded="false">'
            + '<div id="results" role="listbox"><ul role="none">'
            + '<li role="none"><button role="option" data-value="a">A</button></li>'
            + '<li role="none"><button role="option" data-value="b">B</button></li>'
            + '</ul></div>';

        $input = document.querySelector('input')!;
        $listbox = document.getElementById('results')!;
    });

    const options = () => [...$listbox.querySelectorAll('[data-value]')] as HTMLElement[];

    it('numbers the options after the listbox, since the endpoint cannot know the page\'s ids', () => {
        combobox($input, $listbox, '[data-value]').refresh();

        expect(options().map(($option) => $option.id)).toEqual(['results-option-0', 'results-option-1']);
        expect(options().map(($option) => $option.getAttribute('aria-selected'))).toEqual(['false', 'false']);
    });

    it('moves the active option without moving the focus', () => {
        const state = combobox($input, $listbox, '[data-value]');
        state.refresh();
        $input.focus();

        state.move(1);
        state.move(1);
        state.move(1);

        expect($input.getAttribute('aria-activedescendant')).toBe('results-option-1');
        expect(options()[1].getAttribute('aria-selected')).toBe('true');
        expect(options()[0].getAttribute('aria-selected')).toBe('false');
        expect(state.getActive()).toBe(options()[1]);
        expect(document.activeElement).toBe($input);
    });

    it('returns to the input above the first option', () => {
        const state = combobox($input, $listbox, '[data-value]');
        state.refresh();

        state.move(1);
        state.move(-1);

        expect($input.hasAttribute('aria-activedescendant')).toBe(false);
        expect(state.getActive()).toBeNull();
    });

    it('drops the active option when the listbox closes or is refilled', () => {
        const state = combobox($input, $listbox, '[data-value]');
        state.refresh();
        state.setExpanded(true);
        state.move(1);

        expect($input.getAttribute('aria-expanded')).toBe('true');

        state.setExpanded(false);

        expect($input.getAttribute('aria-expanded')).toBe('false');
        expect($input.hasAttribute('aria-activedescendant')).toBe(false);

        state.move(1);
        $listbox.innerHTML = '<button role="option" data-value="c">C</button>';
        state.refresh();

        expect(state.getActive()).toBeNull();
        expect($input.hasAttribute('aria-activedescendant')).toBe(false);
    });
});
