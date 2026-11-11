/**
 * Keyboard shortcuts (monorepo #760). Always on: ⌘S / Ctrl+S submits the page's form, ⌘K / Ctrl+K opens the search.
 * Letter shortcuts, which the account can turn off (WCAG 2.1.4): `m`, `t` or `a` labels the aside's links, the tabs or
 * the header's actions with a letter each, typed next; `?` lists them, `/` searches, holding Ctrl shows every label.
 * A letter comes from the element's own label (a word's initial first), so nothing is declared per link; an element
 * may name its own with `data-shortcut`.
 */
import {createModal} from './modals';

interface Group {
    key: string;
    selector: string;
    open?: () => (() => void) | void;
}

const ACTIONS_TOGGLE = '#wrap .header .dropdown-actions > [popovertarget]';

const GROUPS: Group[] = [
    {key: 'm', selector: '.aside .nav-link[href]'},
    {key: 't', selector: '#wrap .tabs:not(.translation-tabs) .nav-link'},
    {
        key: 'a',
        selector: '#wrap .header .btn:not([popovertarget]):not(dialog *)',
        // The dropdown's items are hidden until it opens, which its own toggle does (and positions it).
        open: () => {
            const $toggle = document.querySelector<HTMLElement>(ACTIONS_TOGGLE);
            const $menu = $toggle ? document.getElementById($toggle.getAttribute('popovertarget') ?? '') : null;

            if (!$toggle || !$menu || $menu.matches(':popover-open')) {
                return;
            }

            $toggle.click();
            return () => $menu.matches(':popover-open') && $menu.hidePopover();
        },
    },
];

const KEYS = 'abcdefghijklmnopqrstuvwxyz0123456789';
const REVEAL_DELAY = 600;

export const isMac = (): boolean => /Mac|iPhone|iPad/.test(navigator.platform);

export const normalizeKey = (text: string): string => text.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();

const getLabel = ($el: Element): string => {
    const $label = $el.querySelector('.nav-link-label');

    return ($label?.textContent || $el.getAttribute('aria-label') || $el.textContent || '').trim();
};

const isVisible = ($el: Element): boolean => $el.checkVisibility?.() ?? $el.getClientRects().length > 0;

/**
 * In document order, an element's `data-shortcut` first, then its words' initials, then any of its letters, then any
 * letter or digit left: the same page assigns the same letters every time.
 */
export const assignKeys = ($elements: Element[]): Map<string, Element> => {
    const keys = new Map<string, Element>();

    $elements.forEach(($el) => {
        const own = normalizeKey($el.getAttribute('data-shortcut') ?? '');

        if (own && !keys.has(own)) {
            keys.set(own, $el);
        }
    });

    $elements.forEach(($el) => {
        if ([...keys.values()].includes($el)) {
            return;
        }

        const words = normalizeKey(getLabel($el)).split(/[^a-z0-9]+/).filter(Boolean);
        const candidates = [...words.map((word) => word[0]), ...words.join(''), ...KEYS];
        const key = candidates.find((candidate) => KEYS.includes(candidate) && !keys.has(candidate));

        if (key) {
            keys.set(key, $el);
        }
    });

    return keys;
};

export const isTyping = (target: EventTarget | null): boolean => target instanceof Element
    && (target.matches('input, select, textarea, [contenteditable]:not([contenteditable="false"])')
        || target.closest('[contenteditable]:not([contenteditable="false"]), .tox') !== null);

const areLettersEnabled = (): boolean => document.getElementById('keyboard-shortcuts')?.dataset.letters === '1';

let hints: HTMLElement | null = null;
let pending: {keys: Map<string, Element>; close?: () => void} | null = null;
let revealTimer: number | undefined;

// A label covers the element's icon, which the collapsed aside still shows; without one, it goes before the text.
const getIcon = ($el: Element): Element | null => $el.querySelector('.nav-link-icon, .fas, .far, .fab');

const showHints = (entries: [string, Element][]): void => {
    hideHints();

    hints = document.createElement('div');
    hints.className = 'shortcut-hints';
    hints.setAttribute('aria-hidden', 'true');

    entries.forEach(([label, $el]) => {
        const $icon = getIcon($el);
        const rect = ($icon ?? $el.querySelector('.nav-link-label') ?? $el).getBoundingClientRect();
        const $hint = document.createElement('kbd');

        $hint.className = $icon ? 'shortcut-hint' : 'shortcut-hint shortcut-hint-before';
        $hint.textContent = label;

        $hint.style.left = `${Math.max(0, $icon ? rect.left + rect.width / 2 : rect.left)}px`;
        $hint.style.top = `${Math.max(0, rect.top + rect.height / 2)}px`;
        hints!.appendChild($hint);
    });

    // A popover of its own puts the labels in the top layer, above an open dropdown.
    hints.popover = 'manual';
    document.body.appendChild(hints);
    hints.showPopover?.();
};

const hideHints = (): void => {
    hints?.remove();
    hints = null;
};

export const cancel = (): void => {
    clearTimeout(revealTimer);
    pending?.close?.();
    pending = null;
    hideHints();
};

const getGroupElements = (group: Group): Element[] => [...document.querySelectorAll(group.selector)].filter(isVisible);

const start = (group: Group): boolean => {
    const close = group.open?.() ?? undefined;
    const keys = assignKeys(getGroupElements(group));

    if (!keys.size) {
        close?.();
        return false;
    }

    pending = {keys, close};

    // A dropdown is positioned after it opens, so its items are measured a frame later.
    const show = () => pending?.keys === keys && showHints([...keys]);
    close ? requestAnimationFrame(() => requestAnimationFrame(show)) : show();

    return true;
};

const reveal = (): void => {
    const entries: [string, Element][] = [];

    GROUPS.forEach((group) => {
        assignKeys(getGroupElements(group)).forEach(($el, key) => entries.push([`${group.key} ${key}`, $el]));
    });

    // The dropdown's items stay hidden, so its toggle shows the key that opens it.
    const $toggle = document.querySelector(ACTIONS_TOGGLE);

    if ($toggle && isVisible($toggle)) {
        entries.push(['a', $toggle]);
    }

    showHints(entries);
};

const activate = ($el: Element): void => {
    if ($el instanceof HTMLElement) {
        $el.focus({preventScroll: true});
        $el.click();
    }
};

export const findForm = (): HTMLFormElement | null => {
    const hasSubmit = ($form: HTMLFormElement | null | undefined): $form is HTMLFormElement => !!$form
        && $form.method === 'post'
        && $form.querySelector('[type="submit"]:not(:disabled)') !== null;

    const $focused = document.activeElement?.closest('form');

    if (hasSubmit($focused)) {
        return $focused;
    }

    if (document.querySelector('dialog[open]')) {
        return null;
    }

    return [...document.querySelectorAll<HTMLFormElement>('#wrap form.form')].find(hasSubmit) ?? null;
};

export const submitForm = ($form: HTMLFormElement): void => {
    $form.requestSubmit($form.querySelector<HTMLElement>('[type="submit"]:not(:disabled)'));
};

export const focusSearch = (): boolean => {
    const $input = document.querySelector<HTMLInputElement>('[data-search] input[type="search"]');

    if (!$input) {
        return false;
    }

    // The collapsed box keeps its input rendered, a sliver at no opacity, so only the toggle says it is closed.
    const $toggle = $input.closest('[data-search]')?.querySelector<HTMLElement>('[data-search-toggle]');

    if ($toggle?.getAttribute('aria-expanded') === 'false') {
        $toggle.click();
    }

    $input.focus();
    $input.select();

    return true;
};

const showHelp = (): void => {
    const $template = document.getElementById('keyboard-shortcuts');

    if (!($template instanceof HTMLTemplateElement) || document.querySelector('dialog[open]')) {
        return;
    }

    const $content = $template.content.cloneNode(true) as DocumentFragment;

    if (isMac()) {
        $content.querySelectorAll('[data-modifier]').forEach(($kbd) => $kbd.textContent = '⌘');
    }

    const $wrapper = document.createElement('div');
    $wrapper.appendChild($content);

    createModal($template.dataset.title ?? '', $wrapper.innerHTML, $template.dataset.closeLabel);
};

const onModifier = (event: KeyboardEvent): boolean => {
    if (!(isMac() ? event.metaKey : event.ctrlKey) || event.altKey || event.shiftKey || event.repeat) {
        return false;
    }

    const key = event.key.toLowerCase();

    if (key === 's') {
        const $form = findForm();

        if ($form) {
            event.preventDefault();
            submitForm($form);
        }

        return true;
    }

    // TinyMCE's dialogs live in the page, and ⌘K there is "insert link".
    if (key === 'k' && !(event.target instanceof Element && event.target.closest('.tox'))) {
        if (focusSearch()) {
            event.preventDefault();
        }

        return true;
    }

    return false;
};

export const onKeydown = (event: KeyboardEvent): void => {
    clearTimeout(revealTimer);

    if (event.defaultPrevented || event.isComposing) {
        return;
    }

    if (event.key === 'Control' && !event.repeat && !event.metaKey && !event.altKey && !event.shiftKey) {
        if (areLettersEnabled() && !pending && !isTyping(event.target)) {
            revealTimer = window.setTimeout(reveal, REVEAL_DELAY);
        }

        return;
    }

    if (!pending) {
        hideHints();
    }

    if (onModifier(event) || event.ctrlKey || event.metaKey || event.altKey) {
        cancel();
        return;
    }

    if (!areLettersEnabled() || isTyping(event.target) || event.getModifierState?.('CapsLock')) {
        return;
    }

    if (pending) {
        const $el = event.key === 'Escape' ? undefined : pending.keys.get(normalizeKey(event.key));

        if (event.key === 'Shift') {
            return;
        }

        event.preventDefault();

        if (!$el) {
            cancel();
            return;
        }

        // The dropdown stays open as a click leaves it: an item may open a modal inside it.
        pending = null;
        hideHints();
        activate($el);

        return;
    }

    if (document.querySelector('dialog[open]') || event.repeat) {
        return;
    }

    if (event.key === '?') {
        event.preventDefault();
        showHelp();
        return;
    }

    // Before the Shift check: a German keyboard types `/` with Shift.
    if (event.key === '/') {
        if (focusSearch()) {
            event.preventDefault();
        }

        return;
    }

    if (event.shiftKey) {
        return;
    }

    const group = GROUPS.find((group) => group.key === event.key);

    if (group && start(group)) {
        event.preventDefault();
    }
};

document.addEventListener('keydown', onKeydown);

document.addEventListener('keyup', (event: KeyboardEvent) => {
    if (event.key === 'Control') {
        clearTimeout(revealTimer);

        if (!pending) {
            hideHints();
        }
    }
});

// The labels sit where the elements were: anything that moves them, or the page itself, ends the sequence.
['mousedown', 'scroll', 'resize', 'blur', 'htmx:before:swap'].forEach((name) => {
    window.addEventListener(name, cancel, true);
});
