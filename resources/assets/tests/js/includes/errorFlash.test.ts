import {afterEach, describe, expect, it, vi} from 'vitest';
import {showErrorFlash} from '../../../src/js/includes/errorFlash';

describe('showErrorFlash', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('fills the template with the message as text', () => {
        document.body.innerHTML = '<div id="flashes"></div>'
            + '<template id="error-flash"><div class="alert"><div class="alert-content"></div></div></template>';

        showErrorFlash('<b>Müller</b> ist zu groß');
        showErrorFlash('Second');

        const $contents = document.querySelectorAll('#flashes .alert-content');
        expect([...$contents].map(($content) => $content.textContent)).toEqual(['<b>Müller</b> ist zu groß', 'Second']);
        expect(document.querySelector('#flashes b')).toBeNull();
        expect(document.querySelector<HTMLTemplateElement>('#error-flash')!.content.querySelector('.alert-content')!.textContent).toBe('');
    });

    it('alerts on a page without the template', () => {
        const alert = vi.fn();
        vi.stubGlobal('alert', alert);
        document.body.innerHTML = '<div id="flashes"></div>';

        showErrorFlash('Refused');

        expect(alert).toHaveBeenCalledWith('Refused');
    });
});
