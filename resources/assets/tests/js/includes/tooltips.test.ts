import {beforeEach, describe, expect, it} from 'vitest';
import initHotspot from '../../../src/js/includes/tooltips';

const render = (html: string): HTMLElement => {
    document.body.innerHTML = html;
    const $element = document.body.firstElementChild as HTMLElement;
    initHotspot($element);

    return $element;
};

describe('tooltips', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    it('renders the title as text, never as markup', () => {
        const $button = render('<button data-tooltip title="&lt;img src=x onerror=alert(1)&gt;"></button>');
        $button.dispatchEvent(new MouseEvent('mouseenter'));

        const $inner = document.querySelector('.tooltip-inner')!;

        expect($inner.textContent).toBe('<img src=x onerror=alert(1)>');
        expect(document.querySelector('.tooltip img')).toBeNull();
    });

    it('keeps the title as the accessible name of an icon-only element', () => {
        const $button = render('<button data-tooltip title="Delete"><i class="fas fa-trash"></i></button>');

        expect($button.hasAttribute('title')).toBe(false);
        expect($button.getAttribute('aria-label')).toBe('Delete');
    });

    it('keeps the title as the description of an element with a label of its own', () => {
        const $link = render('<a href="#" data-tooltip title="Open in admin">Entry</a>');

        expect($link.hasAttribute('aria-label')).toBe(false);
        expect($link.getAttribute('aria-description')).toBe('Open in admin');
    });

    it('leaves an existing aria-label alone', () => {
        const $button = render('<button data-tooltip title="Pin" aria-label="Pin the menu"></button>');

        expect($button.getAttribute('aria-label')).toBe('Pin the menu');
    });

    it('shows on focus and hides on blur and Escape', () => {
        const $button = render('<button data-tooltip title="Delete"></button>');

        $button.dispatchEvent(new FocusEvent('focus'));
        expect(document.querySelector('.tooltip')).not.toBeNull();

        $button.dispatchEvent(new FocusEvent('blur'));
        expect(document.querySelector('.tooltip')).toBeNull();

        $button.dispatchEvent(new FocusEvent('focus'));
        $button.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape'}));
        expect(document.querySelector('.tooltip')).toBeNull();
    });
});
