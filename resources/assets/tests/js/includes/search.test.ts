import {beforeEach, describe, expect, it, vi} from 'vitest';

// happy-dom has no popover API, so the open state is a flag on the element; an earlier test's document listeners
// still run, and must not see this one's popover.
vi.mock('../../../src/js/includes/popover', () => ({
    openUnder: (_$anchor: HTMLElement, $popover: HTMLElement) => {
        $popover.dataset.open = 'true';
        return () => undefined;
    },
}));

// htmx needs `XPathEvaluator`, which happy-dom lacks; only the Enter key without an active result reaches it.
const htmx = vi.hoisted(() => ({ajax: vi.fn()}));
vi.mock('htmx.org', () => ({default: htmx}));

import search from '../../../src/js/includes/search';

describe('search', () => {
    let $input: HTMLInputElement;
    let $results: HTMLElement;

    beforeEach(() => {
        htmx.ajax.mockClear();

        document.body.innerHTML = '<div class="navbar-search" data-search="/admin/search/index">'
            + '<input type="search" name="q" role="combobox" aria-expanded="false" aria-controls="search-results">'
            + '<button type="button" data-search-toggle aria-expanded="false"></button>'
            + '<div id="search-results" role="listbox" data-search-results popover="manual"></div>'
            + '</div>';

        $input = document.querySelector('input')!;
        $results = document.getElementById('search-results')!;

        const $popover = $results;
        const matches = $popover.matches.bind($popover);
        vi.spyOn($popover, 'matches').mockImplementation((selector: string) => selector === ':popover-open'
            ? $popover.dataset.open === 'true'
            : matches(selector));
        $popover.hidePopover = () => delete $popover.dataset.open;

        search(document.querySelector('[data-search]')!);
    });

    const swap = () => {
        $results.innerHTML = '<ul role="none">'
            + '<li role="none"><a role="option" tabindex="-1" href="/admin/user/update?id=1">Ada</a></li>'
            + '<li role="none"><a role="option" tabindex="-1" href="/admin/user/update?id=2">Bob</a></li>'
            + '</ul>';

        $input.dispatchEvent(new CustomEvent('htmx:after:swap', {bubbles: true}));
    };

    const press = (key: string) => {
        const event = new KeyboardEvent('keydown', {key, bubbles: true, cancelable: true});
        $input.dispatchEvent(event);
        return event;
    };

    it('expands the combobox when the results arrive', () => {
        swap();

        expect($input.getAttribute('aria-expanded')).toBe('true');
        expect($results.querySelector('a')?.id).toBe('search-results-option-0');
    });

    it('follows the active result on Enter while the focus stays in the input', () => {
        swap();
        $input.focus();

        const $second = $results.querySelectorAll('a')[1];
        const click = vi.fn((event: Event) => event.preventDefault());
        $second.addEventListener('click', click);

        press('ArrowDown');
        press('ArrowDown');

        expect($input.getAttribute('aria-activedescendant')).toBe('search-results-option-1');
        expect($second.getAttribute('aria-selected')).toBe('true');
        expect(document.activeElement).toBe($input);

        press('Enter');

        expect(click).toHaveBeenCalledOnce();
        expect(htmx.ajax).not.toHaveBeenCalled();
    });

    it('opens the results page on Enter without an active result', () => {
        $input.value = 'Ada';
        swap();
        press('Enter');

        expect(htmx.ajax).toHaveBeenCalledWith('GET', '/admin/search/index?q=Ada', expect.anything());
    });

    it('closes the results on the first Escape and clears the input on the second', () => {
        const $container = document.querySelector('[data-search]')!;
        $container.classList.add('expanded');
        $input.value = 'Ada';
        swap();
        press('ArrowDown');

        expect(press('Escape').defaultPrevented).toBe(true);
        expect($results.dataset.open).toBeUndefined();
        expect($input.getAttribute('aria-expanded')).toBe('false');
        expect($input.hasAttribute('aria-activedescendant')).toBe(false);
        expect($input.value).toBe('Ada');

        press('Escape');

        expect($input.value).toBe('');
        expect($container.classList.contains('expanded')).toBe(false);
    });
});
