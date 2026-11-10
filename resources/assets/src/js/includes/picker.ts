import htmx from 'htmx.org';

import {createModal} from './modals';

export interface Pick {
    value: string;
    title: string;
}

/**
 * Opens the page at `url` in a modal and answers what its `[data-picker-value]` button carried, `null` when closed
 * without a pick. The page renders its content in `#picker`; everything inside navigates within the modal, never
 * `#wrap`, and pushes no URL.
 */
export const openPicker = (url: string, title: string): Promise<Pick | null> => new Promise((resolve) => {
    const $dialog = createModal(title, '<div id="picker"></div>');
    const $body = $dialog.querySelector<HTMLElement>('.modal-body')!;
    let pick: Pick | null = null;

    $dialog.classList.add('modal-picker');

    $body.setAttribute('hx-select:inherited', '#picker');
    $body.setAttribute('hx-target:inherited', '#picker');
    $body.setAttribute('hx-swap:inherited', 'outerHTML');
    $body.setAttribute('hx-push-url:inherited', 'false');
    $body.setAttribute('hx-select-oob:inherited', '');

    $body.addEventListener('click', (event: Event) => {
        const $button = (event.target as Element).closest<HTMLElement>('[data-picker-value]');

        if ($button) {
            event.preventDefault();
            pick = {value: $button.dataset.pickerValue!, title: $button.dataset.pickerTitle ?? ''};
            $dialog.close();
        }
    });

    $dialog.addEventListener('close', () => resolve(pick));

    void htmx.ajax('GET', url, {
        source: $body,
        target: '#picker',
        select: '#picker',
        swap: 'outerHTML',
        push: false,
    });
});

/**
 * A button carrying `data-picker` (the URL) and `data-picker-input` (the input's id) writes the pick into the input,
 * and its title into the element `data-picker-label` names, if there is one.
 */
export default ($button: HTMLButtonElement) => {
    $button.addEventListener('click', async () => {
        const $input = document.getElementById($button.dataset.pickerInput!) as HTMLInputElement | null;
        const pick = await openPicker($button.dataset.picker!, $button.dataset.pickerTitle ?? '');

        if ($input && pick) {
            const $label = $button.dataset.pickerLabel ? document.getElementById($button.dataset.pickerLabel) : null;

            if ($label) {
                $label.textContent = pick.title;
            }

            $input.value = pick.value;
            $input.dispatchEvent(new Event('input', {bubbles: true}));
            $input.dispatchEvent(new Event('change', {bubbles: true}));
        }
    });
}
