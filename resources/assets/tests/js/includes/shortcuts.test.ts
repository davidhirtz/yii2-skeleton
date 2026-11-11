import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {assignKeys, cancel, findForm, isTyping, normalizeKey} from '../../../src/js/includes/shortcuts';

const press = (key: string, init: KeyboardEventInit = {}, $target: Element = document.body): KeyboardEvent => {
    const event = new KeyboardEvent('keydown', {key, bubbles: true, cancelable: true, ...init});
    $target.dispatchEvent(event);

    return event;
};

const link = (label: string, href: string, attributes = ''): string =>
    `<a class="nav-link" href="${href}" ${attributes}><span class="nav-link-label">${label}</span></a>`;

describe('shortcuts', () => {
    beforeEach(() => {
        Object.defineProperty(navigator, 'platform', {value: 'Win32', configurable: true});
        Element.prototype.checkVisibility = () => true;

        document.body.innerHTML = `
            <aside class="aside">${link('Einträge', '/entries')}${link('Entwürfe', '/drafts')}${link('Ålesund', '/a')}</aside>
            <div id="wrap">
                <template id="keyboard-shortcuts" data-letters="1" data-title="Keyboard shortcuts" data-close-label="Close"><table><tr><th><kbd data-modifier>Ctrl</kbd> S</th></tr></table></template>
                <div class="header"><button class="btn" data-shortcut="v">View</button><div class="dropdown-actions"><button class="btn" popovertarget="menu">…</button><div id="menu" popover><button class="btn">Delete</button></div></div></div>
                <ul class="tabs">${link('Page', '/page')}${link('Sections', '/sections', 'data-shortcut="x"')}</ul>
                <form class="form" method="get"><button type="submit">Filter</button></form>
                <form class="form" method="post"><input name="name"><button type="submit">Save</button></form>
            </div>`;
    });

    afterEach(() => {
        cancel();
        document.querySelectorAll('dialog').forEach(($dialog) => $dialog.remove());
    });

    it('takes a word\'s initial first, then its other letters, in document order', () => {
        const keys = assignKeys([...document.querySelectorAll('.aside a')]);

        expect([...keys].map(([key, $el]) => `${key} ${$el.getAttribute('href')}`)).toEqual([
            'e /entries',
            'n /drafts',
            'a /a',
        ]);
    });

    it('lets an element name its own key', () => {
        const keys = assignKeys([...document.querySelectorAll('.tabs a')]);

        expect(keys.get('x')?.getAttribute('href')).toBe('/sections');
        expect(keys.get('p')?.getAttribute('href')).toBe('/page');
    });

    it('reads diacritics and NFD as their base letter', () => {
        expect(normalizeKey('Mu\u{308}ller')).toBe('muller');
        expect(normalizeKey('Ä')).toBe('a');
    });

    it('opens the link of the letter typed after the group key', () => {
        const clicked = vi.fn((event: Event) => event.preventDefault());
        document.querySelector('a[href="/drafts"]')!.addEventListener('click', clicked);

        expect(press('m').defaultPrevented).toBe(true);
        expect(document.querySelectorAll('.shortcut-hint')).toHaveLength(3);

        press('n');

        expect(clicked).toHaveBeenCalledOnce();
        expect(document.querySelector('.shortcut-hints')).toBeNull();
    });

    it('ends the sequence on Escape or a letter without a target', () => {
        press('t');
        expect(press('Escape').defaultPrevented).toBe(true);
        expect(document.querySelector('.shortcut-hints')).toBeNull();

        press('t');
        press('q');
        expect(document.querySelector('.shortcut-hints')).toBeNull();
    });

    it('labels every group while Ctrl is held, the dropdown by its toggle', () => {
        vi.useFakeTimers();
        Element.prototype.checkVisibility = function (this: Element) {
            return !this.closest('[popover]');
        };

        press('Control', {ctrlKey: true});
        vi.advanceTimersByTime(600);
        vi.useRealTimers();

        expect([...document.querySelectorAll('.shortcut-hint')].map(($hint) => $hint.textContent)).toEqual([
            'me', 'mn', 'ma', 'tx', 'tp', 'av', 'a',
        ]);
    });

    it('opens the collapsed search on Ctrl+K', () => {
        document.body.insertAdjacentHTML('beforeend', '<div data-search><button data-search-toggle aria-expanded="false"></button><input type="search"></div>');
        const $toggle = document.querySelector<HTMLElement>('[data-search-toggle]')!;
        const toggled = vi.fn();
        $toggle.addEventListener('click', toggled);

        expect(press('k', {ctrlKey: true}).defaultPrevented).toBe(true);
        expect(toggled).toHaveBeenCalledOnce();
        expect(document.activeElement).toBe(document.querySelector('[data-search] input'));
    });

    it('searches on /, typed with Shift on a German keyboard', () => {
        document.body.insertAdjacentHTML('beforeend', '<div data-search><input type="search"></div>');

        expect(press('/', {shiftKey: true}).defaultPrevented).toBe(true);
        expect(document.activeElement).toBe(document.querySelector('[data-search] input'));
    });

    it('ignores letters while typing, with a modifier or when the account turned them off', () => {
        const $input = document.querySelector('input')!;

        expect(isTyping($input)).toBe(true);
        expect(press('m', {}, $input).defaultPrevented).toBe(false);
        expect(press('m', {altKey: true}).defaultPrevented).toBe(false);

        document.getElementById('keyboard-shortcuts')!.dataset.letters = '0';
        expect(press('m').defaultPrevented).toBe(false);
        expect(document.querySelector('.shortcut-hints')).toBeNull();
    });

    it('saves the page\'s form, never a search form', () => {
        const $form = document.querySelector<HTMLFormElement>('form[method="post"]')!;
        const submit = vi.fn();
        $form.requestSubmit = submit;

        expect(findForm()).toBe($form);
        expect(press('s', {ctrlKey: true}).defaultPrevented).toBe(true);
        expect(submit).toHaveBeenCalledWith($form.querySelector('button'));
    });

    it('saves even with letter shortcuts turned off', () => {
        document.getElementById('keyboard-shortcuts')!.dataset.letters = '0';
        const $form = document.querySelector<HTMLFormElement>('form[method="post"]')!;
        $form.requestSubmit = vi.fn();

        press('s', {ctrlKey: true}, document.querySelector('input')!);

        expect($form.requestSubmit).toHaveBeenCalledOnce();
    });

    it('lists the shortcuts on ?, with the platform\'s modifier', () => {
        Object.defineProperty(navigator, 'platform', {value: 'MacIntel', configurable: true});
        HTMLDialogElement.prototype.showModal ??= function (this: HTMLDialogElement) {
            this.open = true;
        };

        press('?', {shiftKey: true});

        const $dialog = document.querySelector('dialog');
        expect($dialog?.querySelector('.modal-title')?.textContent).toBe('Keyboard shortcuts');
        expect($dialog?.querySelector('kbd')?.textContent).toBe('⌘');
    });
});
