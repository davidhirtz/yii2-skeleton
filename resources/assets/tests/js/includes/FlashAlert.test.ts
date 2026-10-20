import {beforeEach, describe, expect, it} from 'vitest';
import '../../../src/js/includes/FlashAlert';

const flash = (text: string, type = 'danger') => `<flash-alert><div class="alert" data-alert="${type}"><div class="alert-content">${text}</div><button data-close></button></div></flash-alert>`;
const flashes = (count: number) => Array.from({length: count}, (_, i) => flash(`Flash ${i}`)).join('');
const live = () => [...document.querySelectorAll('#flashes flash-alert:not(.dismissed)')].map(($e) => $e.textContent);
const settle = () => new Promise((resolve) => setTimeout(resolve));

describe('flash-alert', () => {
    beforeEach(() => {
        document.body.innerHTML = '<div id="flashes"></div>';
    });

    it('keeps the newest five of a batch that arrives at once', async () => {
        document.getElementById('flashes')!.insertAdjacentHTML('beforeend', flashes(50));
        await settle();

        expect(live()).toEqual(['Flash 45', 'Flash 46', 'Flash 47', 'Flash 48', 'Flash 49']);
        expect(document.querySelectorAll('#flashes flash-alert')).toHaveLength(5);
    });

    it('closes the oldest alerts of an earlier response', async () => {
        const $flashes = document.getElementById('flashes')!;
        $flashes.insertAdjacentHTML('beforeend', flashes(5));
        await settle();

        $flashes.insertAdjacentHTML('beforeend', flash('Newer') + flash('Newest'));
        await settle();

        expect(live()).toEqual(['Flash 2', 'Flash 3', 'Flash 4', 'Newer', 'Newest']);
    });

    it('numbers the alerts a batch still shows for the stagger', async () => {
        const $flashes = document.getElementById('flashes')!;
        const indexes = () => [...$flashes.querySelectorAll<HTMLElement>('flash-alert:not(.dismissed)')]
            .map(($e) => $e.style.getPropertyValue('--flash-index'));

        $flashes.insertAdjacentHTML('beforeend', flashes(8));
        await settle();

        expect(indexes()).toEqual(['0', '1', '2', '3', '4']);

        $flashes.insertAdjacentHTML('beforeend', flash('Later'));
        await settle();

        expect(indexes()).toEqual(['1', '2', '3', '4', '0']);
    });

    it('removes a closed alert', async () => {
        document.getElementById('flashes')!.insertAdjacentHTML('beforeend', flash('Closed'));
        document.querySelector<HTMLElement>('[data-close]')!.click();

        await settle();

        expect(document.querySelector('flash-alert')).toBeNull();
    });
});
