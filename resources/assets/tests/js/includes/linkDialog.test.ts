import {beforeEach, describe, expect, it, vi} from 'vitest';

const ajax = vi.hoisted(() => vi.fn(async () => {}));

vi.mock('htmx.org', () => ({default: {ajax}}));

const {openLinkDialog} = await import('../../../src/js/includes/linkDialog');

const values = {href: 'entry:12', text: 'Ångström 東京 🚀', title: '', target: '', class: ''};

describe('linkDialog', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        ajax.mockClear();
        HTMLDialogElement.prototype.showModal ??= function (this: HTMLDialogElement) {
            this.open = true;
        };
    });

    it('loads the form with the link it edits and the classes, keeping it out of the history', () => {
        void openLinkDialog('/admin/link?tenant=1', 'Link', values, [{title: 'Button', value: 'btn'}]);

        const [method, url, options] = ajax.mock.calls[0] as unknown as [string, string, Record<string, unknown>];
        const params = new URL(url, 'http://localhost').searchParams;

        expect(method).toBe('GET');
        expect(params.get('tenant')).toBe('1');
        expect(params.get('href')).toBe('entry:12');
        expect(params.get('text')).toBe('Ångström 東京 🚀');
        expect(JSON.parse(params.get('classes')!)).toEqual({btn: 'Button'});
        expect(options).toEqual(expect.objectContaining({select: '#link-dialog', target: '#link-dialog', push: false}));
    });

    it('answers what the form holds on submit and closes', async () => {
        const result = openLinkDialog('/admin/link', 'Link', values);

        document.getElementById('link-dialog')!.outerHTML = `<form id="link-dialog" data-link-dialog>
            <input name="href" value="/kontakt#form"><input name="text" value=" Müller ">
            <input name="title" value="Kontakt"><input name="target" type="checkbox" value="_blank" checked>
            <select name="class"><option value="btn" selected>Button</option></select>
            <button type="submit"></button></form>`;

        document.querySelector<HTMLButtonElement>('[type="submit"]')!.click();

        expect(await result).toEqual({href: '/kontakt#form', text: 'Müller', title: 'Kontakt', target: '_blank', class: 'btn'});
        expect(document.querySelector('dialog')).toBeNull();
    });

    it('has a close button when given its label, answering null', async () => {
        const result = openLinkDialog('/admin/link', 'Link', values, [], 'Schließen');
        const $close = document.querySelector<HTMLButtonElement>('dialog .modal-header button')!;

        expect($close.getAttribute('aria-label')).toBe('Schließen');

        $close.click();

        expect(await result).toBeNull();
    });

    it('answers null when closed without a submit', async () => {
        const result = openLinkDialog('/admin/link', 'Link', values);
        document.querySelector('dialog')!.close();

        expect(await result).toBeNull();
    });
});
