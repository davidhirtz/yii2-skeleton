import {beforeEach, describe, expect, it} from 'vitest';

import selectLink from '../../../src/js/includes/selectLink';

describe('selectLink', () => {
    let $select: HTMLSelectElement;
    let $link: HTMLAnchorElement;

    beforeEach(() => {
        document.body.innerHTML = '<div class="input-group">'
            + '<select data-select-link><option value=""></option>'
            + '<option value="9" data-url="/admin/media/file/update?id=9">Москва.jpg</option></select>'
            + '<a href="#" hidden data-select-link-target>Open</a>'
            + '<a href="#" class="reorder-handle">Move</a>'
            + '</div>';

        $select = document.querySelector('select')!;
        $link = document.querySelector('[data-select-link-target]')!;

        selectLink($select);
    });

    it('points the link of an input group at the selected option', () => {
        $select.value = '9';
        $select.dispatchEvent(new Event('change'));

        expect($link.getAttribute('href')).toBe('/admin/media/file/update?id=9');
        expect($link.hidden).toBe(false);
        expect(document.querySelector('.reorder-handle')?.getAttribute('href')).toBe('#');
    });

    it('hides the link for the prompt', () => {
        $link.hidden = false;
        $select.dispatchEvent(new Event('change'));

        expect($link.hidden).toBe(true);
    });
});
