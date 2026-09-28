import {describe, expect, it} from 'vitest';
import sortableOrder from '../../../src/js/includes/sortableOrder';

describe('sortableOrder', () => {
    it('posts one indexed value per row, the model name keeping its dashes', () => {
        document.body.innerHTML = '<table><tbody><tr id="hotspot-asset-5"></tr><tr id="hotspot-asset-2"></tr>'
            + '<tr id="hotspot-asset-9"></tr></tbody></table>';

        expect(sortableOrder(document.querySelector('tbody')!)).toEqual({
            'hotspot-asset[0]': '5',
            'hotspot-asset[1]': '2',
            'hotspot-asset[2]': '9',
        });
    });
});
