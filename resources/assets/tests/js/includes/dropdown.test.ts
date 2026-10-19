import {describe, expect, it} from 'vitest';
import '../../../src/js/includes/dropdown';

const toggle = ($popover: HTMLElement, type: string, newState: string) => {
    $popover.dispatchEvent(Object.assign(new Event(type), {newState}));
};

describe('dropdown', () => {
    it('positions a menu whose button was never initialised', async () => {
        document.body.innerHTML = '<div class="dropdown"><button popovertarget="menu">Actions</button>'
            + '<div id="menu" popover class="dropdown-menu"><a href="#">One</a><a href="#">Two</a></div></div>';

        const $popover = document.getElementById('menu')!;

        toggle($popover, 'beforetoggle', 'open');
        expect($popover.style.visibility).toBe('hidden');

        toggle($popover, 'toggle', 'open');
        await new Promise((resolve) => setTimeout(resolve, 10));

        expect($popover.style.visibility).toBe('visible');
        expect($popover.style.left).toMatch(/px$/);
        expect(document.documentElement.style.overflow).toBe('hidden');

        toggle($popover, 'toggle', 'closed');
        expect(document.documentElement.style.overflow).toBe('');
    });

    it('leaves a popover without a button alone', () => {
        document.body.innerHTML = '<div id="results" popover="manual"></div>';

        const $popover = document.getElementById('results')!;

        toggle($popover, 'beforetoggle', 'open');
        toggle($popover, 'toggle', 'open');

        expect($popover.style.visibility).toBe('');
        expect(document.documentElement.style.overflow).toBe('');
    });
});
