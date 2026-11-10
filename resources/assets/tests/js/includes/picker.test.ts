import {beforeEach, describe, expect, it, vi} from 'vitest';

const ajax = vi.hoisted(() => vi.fn(async () => {}));

vi.mock('htmx.org', () => ({default: {ajax}}));

const {openPicker, default: picker} = await import('../../../src/js/includes/picker');

const settle = () => new Promise((resolve) => setTimeout(resolve));

const fill = (html: string) => {
    document.getElementById('picker')!.innerHTML = html;
};

describe('picker', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        ajax.mockClear();
        HTMLDialogElement.prototype.showModal ??= function (this: HTMLDialogElement) {
            this.open = true;
        };
    });

    it('loads the page into the modal, keeping its navigation inside', () => {
        void openPicker('/admin/pick', 'Pick');

        const $body = document.querySelector('dialog.modal-picker .modal-body')!;

        expect(ajax).toHaveBeenCalledWith('GET', '/admin/pick', expect.objectContaining({select: '#picker', target: '#picker', push: false}));
        expect($body.getAttribute('hx-target:inherited')).toBe('#picker');
        expect($body.getAttribute('hx-push-url:inherited')).toBe('false');
        expect($body.getAttribute('hx-select-oob:inherited')).toBe('');
    });

    it('answers the pick and closes', async () => {
        const pick = openPicker('/admin/pick', 'Pick');
        fill('<button data-picker-value="entry:12" data-picker-title="Ångström 東京 🚀"></button>');

        document.querySelector<HTMLElement>('[data-picker-value]')!.click();

        expect(await pick).toEqual({value: 'entry:12', title: 'Ångström 東京 🚀'});
        expect(document.querySelector('dialog')).toBeNull();
    });

    it('answers null when closed without a pick', async () => {
        const pick = openPicker('/admin/pick', 'Pick');
        document.querySelector('dialog')!.close();

        expect(await pick).toBeNull();
    });

    it('writes the pick into the input a button names', async () => {
        document.body.innerHTML = '<input id="link" value="/old"><div id="link-hint">Old</div><button data-picker="/admin/pick" data-picker-input="link" data-picker-label="link-hint"></button>';
        const $input = document.querySelector<HTMLInputElement>('#link')!;
        const changed = vi.fn();
        $input.addEventListener('change', changed);

        picker(document.querySelector('button')!);
        document.querySelector('button')!.click();
        fill('<button data-picker-value="entry:7" data-picker-title="Karriere — /karriere"></button>');
        document.querySelector<HTMLElement>('[data-picker-value]')!.click();
        await settle();

        expect($input.value).toBe('entry:7');
        expect(document.getElementById('link-hint')!.textContent).toBe('Karriere — /karriere');
        expect(changed).toHaveBeenCalledOnce();
    });
});
