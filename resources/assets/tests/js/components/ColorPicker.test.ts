import {beforeEach, describe, expect, it} from 'vitest';

await import('../../../src/js/components/ColorPicker');

describe('color-picker', () => {
    let $color: HTMLInputElement;
    let $text: HTMLInputElement;

    beforeEach(() => {
        const $picker = document.createElement('color-picker');
        $picker.innerHTML = '<input type="color" value="#000000" data-unset required><input type="text" value="">';
        [$color, $text] = Array.from($picker.querySelectorAll('input'));

        document.body.replaceChildren($picker);
    });

    const change = ($input: HTMLInputElement, value: string) => {
        $input.value = value;
        $input.dispatchEvent(new Event('change'));
    };

    it('clears the unset state when a color is typed', () => {
        change($text, 'f00');

        expect($color.value).toBe('#ff0000');
        expect($color.hasAttribute('data-unset')).toBe(false);
    });

    it('clears the unset state when a color is picked', () => {
        change($color, '#00ff00');

        expect($text.value).toBe('#00ff00');
        expect($color.hasAttribute('data-unset')).toBe(false);
    });

    it('marks the color as unset when the text is cleared', async () => {
        change($text, '#ff0000');
        await new Promise((resolve) => setTimeout(resolve, 110));
        change($text, '');

        expect($color.value).toBe('#ff0000');
        expect($color.hasAttribute('data-unset')).toBe(true);
    });
});
