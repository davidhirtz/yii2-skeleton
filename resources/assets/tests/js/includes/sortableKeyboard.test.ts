import {beforeEach, describe, expect, it} from 'vitest';
import {moveRowByKey} from '../../../src/js/includes/sortableKeyboard';

const row = (id: string) => `<tr id="entry-${id}"><td><button class="sortable-handle" id="handle-${id}"></button></td></tr>`;

const press = (key: string, id: string, $list: HTMLElement) => {
    const $handle = document.getElementById(`handle-${id}`)!;
    $handle.focus();

    const event = new KeyboardEvent('keydown', {key, bubbles: true, cancelable: true});
    Object.defineProperty(event, 'target', {value: $handle});

    return moveRowByKey(event, $list);
};

describe('moveRowByKey', () => {
    let $list: HTMLElement;

    beforeEach(() => {
        document.body.innerHTML = '<table><tbody data-sort-announcement="Position {position} of {count}">'
            + row('1') + row('2') + row('3') + '</tbody></table>';
        $list = document.querySelector('tbody')!;
    });

    const order = () => [...$list.children].map(($row) => $row.id);

    it('moves the row down and keeps the focus on its handle', () => {
        expect(press('ArrowDown', '1', $list)).toBe(true);

        expect(order()).toEqual(['entry-2', 'entry-1', 'entry-3']);
        expect(document.activeElement?.id).toBe('handle-1');
        expect(document.getElementById('sort-announcement')?.textContent).toBe('Position 2 of 3');
    });

    it('moves the row up', () => {
        press('ArrowUp', '3', $list);

        expect(order()).toEqual(['entry-1', 'entry-3', 'entry-2']);
    });

    it('leaves the ends and other keys to the page', () => {
        expect(press('ArrowUp', '1', $list)).toBe(false);
        expect(press('ArrowDown', '3', $list)).toBe(false);
        expect(press('Enter', '2', $list)).toBe(false);

        expect(order()).toEqual(['entry-1', 'entry-2', 'entry-3']);
    });
});
