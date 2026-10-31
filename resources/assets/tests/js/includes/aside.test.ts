import {beforeEach, describe, expect, it} from 'vitest';
import '../../../src/js/includes/aside';

const isOpen = () => document.documentElement.hasAttribute('data-aside-open');

const click = (selector: string, init: MouseEventInit = {}) => {
    const $element = document.querySelector(selector)!;
    $element.addEventListener('click', (event) => event.preventDefault(), {once: true});
    $element.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true, ...init}));
};

describe('aside', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <button data-aside id="toggle">Menu</button>
            <aside id="aside">
                <ul class="nav aside-nav">
                    <li class="nav-item">
                        <a class="nav-link" id="parent" href="/admin/entry/index">Entries</a>
                        <ul class="nav subnav">
                            <li class="nav-item"><a class="nav-link" id="child" href="/admin/category/index">Categories</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" id="leaf" href="/admin/account/update">Account</a></li>
                    <li class="nav-item"><a class="nav-link" id="blank" href="/" target="_blank">Website</a></li>
                    <li class="nav-item"><button id="button">Search</button></li>
                </ul>
            </aside>
            <main id="main"><a id="outside" href="/admin/user/index">Users</a></main>`;

        document.documentElement.setAttribute('data-aside-open', '');
    });

    it('toggles with the navbar button', () => {
        click('#toggle');
        expect(isOpen()).toBe(false);

        click('#toggle');
        expect(isOpen()).toBe(true);
    });

    it('stays open for a link whose submenu comes next', () => {
        click('#parent');
        expect(isOpen()).toBe(true);
    });

    it('closes for a link without a submenu', () => {
        click('#leaf');
        expect(isOpen()).toBe(false);
    });

    it('closes for a submenu item', () => {
        click('#child');
        expect(isOpen()).toBe(false);
    });

    it('stays open for a link opening another tab', () => {
        click('#blank');
        expect(isOpen()).toBe(true);

        click('#leaf', {metaKey: true});
        expect(isOpen()).toBe(true);

        click('#leaf', {ctrlKey: true});
        expect(isOpen()).toBe(true);
    });

    it('stays open for a control that does not navigate', () => {
        click('#button');
        expect(isOpen()).toBe(true);
    });

    it('closes for a click outside it', () => {
        click('#outside');
        expect(isOpen()).toBe(false);
    });
});
