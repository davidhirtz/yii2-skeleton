import {beforeEach, describe, expect, it, vi} from 'vitest';

// happy-dom has no popover API, so the open state is a flag on the element; an earlier test's document listeners
// still run, and must not see this one's popover.
vi.mock('../../../src/js/includes/popover', () => ({
    openUnder: (_$anchor: HTMLElement, $popover: HTMLElement) => {
        $popover.dataset.open = 'true';
        return () => undefined;
    },
}));

import autocomplete from '../../../src/js/includes/autocomplete';

describe('autocomplete', () => {
    let $container: HTMLElement;
    let $input: HTMLInputElement;
    let $results: HTMLElement;

    beforeEach(() => {
        document.body.innerHTML = '<div data-autocomplete>'
            + '<input role="combobox" aria-expanded="false" aria-controls="results">'
            + '<div id="results" role="listbox" data-autocomplete-results popover="manual"></div>'
            + '</div>';

        $container = document.querySelector('[data-autocomplete]')!;
        $input = $container.querySelector('input')!;
        $results = document.getElementById('results')!;

        const $popover = $results;
        const matches = $popover.matches.bind($popover);
        vi.spyOn($popover, 'matches').mockImplementation((selector: string) => selector === ':popover-open'
            ? $popover.dataset.open === 'true'
            : matches(selector));
        $popover.hidePopover = () => delete $popover.dataset.open;

        autocomplete($container);
    });

    const swap = () => {
        $results.innerHTML = '<ul role="none">'
            + '<li role="none"><button role="option" tabindex="-1" data-autocomplete-value="1">Zürich</button></li>'
            + '<li role="none"><button role="option" tabindex="-1" data-autocomplete-value="2">Bern</button></li>'
            + '</ul>';

        $input.dispatchEvent(new CustomEvent('htmx:after:swap', {bubbles: true}));
    };

    const press = (key: string) => {
        const event = new KeyboardEvent('keydown', {key, bubbles: true, cancelable: true});
        $input.dispatchEvent(event);
        return event;
    };

    it('expands when the options arrive', () => {
        swap();

        expect($input.getAttribute('aria-expanded')).toBe('true');
        expect($results.querySelector('button')?.id).toBe('results-option-0');
    });

    it('moves the active option with the arrow keys while the focus stays in the input', () => {
        swap();
        $input.focus();

        expect(press('ArrowDown').defaultPrevented).toBe(true);
        press('ArrowDown');

        expect($input.getAttribute('aria-activedescendant')).toBe('results-option-1');
        expect(document.activeElement).toBe($input);

        press('ArrowUp');

        expect($input.getAttribute('aria-activedescendant')).toBe('results-option-0');
    });

    it('writes the active option into the input on Enter and collapses', () => {
        const change = vi.fn();
        $input.addEventListener('change', change);

        swap();
        press('ArrowDown');
        press('ArrowDown');

        expect(press('Enter').defaultPrevented).toBe(true);
        expect($input.value).toBe('2');
        expect(change).toHaveBeenCalledOnce();
        expect($input.getAttribute('aria-expanded')).toBe('false');
        expect($input.hasAttribute('aria-activedescendant')).toBe(false);
    });

    it('closes on Escape', () => {
        swap();
        press('ArrowDown');
        press('Escape');

        expect($results.dataset.open).toBeUndefined();
        expect($input.getAttribute('aria-expanded')).toBe('false');
        expect($input.hasAttribute('aria-activedescendant')).toBe(false);
    });

    it('reopens with the arrow key after Escape', () => {
        swap();
        press('Escape');
        press('ArrowDown');

        expect($input.getAttribute('aria-expanded')).toBe('true');
        expect($input.getAttribute('aria-activedescendant')).toBe('results-option-0');
    });

    it('lets Enter submit the form while closed', () => {
        expect(press('Enter').defaultPrevented).toBe(false);
    });

    it('keeps the focus in the input when an option is pressed', () => {
        swap();

        const event = new MouseEvent('mousedown', {bubbles: true, cancelable: true});
        $results.querySelector('button')!.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(true);
    });

    it('picks an option on click', () => {
        swap();
        ($results.querySelector('[data-autocomplete-value="1"]') as HTMLElement).click();

        expect($input.value).toBe('1');
        expect($input.getAttribute('aria-expanded')).toBe('false');
    });
});
