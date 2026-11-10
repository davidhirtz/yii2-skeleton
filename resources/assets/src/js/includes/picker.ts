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
    // A view transition would morph the page's named regions over the modal.
    $body.setAttribute('hx-swap:inherited', 'outerHTML transition:false ignoreTitle:true');
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

    // A form inside may ask for its URL itself (`hx-push-url="true"` on a grid's search), and the `hx-head`
    // extension would merge the picker page's `<title>` into the document.
    const stayInHistory = (event: Event) => {
        if ($dialog.contains((event as CustomEvent).detail?.sourceElement ?? null)) {
            event.preventDefault();
        }
    };

    const keepHead = (event: Event) => {
        if ($dialog.contains((event as CustomEvent).detail?.ctx?.sourceElement ?? null)) {
            event.preventDefault();
        }
    };

    document.addEventListener('htmx:before:history:update', stayInHistory);
    document.body.addEventListener('htmx:head:before:merge', keepHead);

    $dialog.addEventListener('close', () => {
        document.removeEventListener('htmx:before:history:update', stayInHistory);
        document.body.removeEventListener('htmx:head:before:merge', keepHead);
        resolve(pick);
    });

    void htmx.ajax('GET', url, {
        source: $body,
        target: '#picker',
        select: '#picker',
        swap: 'outerHTML transition:false ignoreTitle:true',
        push: false,
    });
});

/**
 * A button carrying `data-picker` (the URL) and `data-picker-input` (the input's id) writes the pick into the input,
 * and its title into the element `data-picker-label` names, created after the input if missing.
 */
export default ($button: HTMLButtonElement) => {
    $button.addEventListener('click', async () => {
        const $input = document.getElementById($button.dataset.pickerInput!) as HTMLInputElement | null;
        const pick = await openPicker($button.dataset.picker!, $button.dataset.pickerTitle ?? '');

        if ($input && pick) {
            const labelId = $button.dataset.pickerLabel;
            let $label = labelId ? document.getElementById(labelId) : null;

            if (labelId && !$label) {
                $label = document.createElement('div');
                $label.id = labelId;
                $label.className = 'form-hint';
                ($input.closest('.input-group') ?? $input).after($label);
                $input.setAttribute('aria-describedby', [$input.getAttribute('aria-describedby'), labelId].filter(Boolean).join(' '));
            }

            if ($label) {
                $label.textContent = pick.title;
            }

            $input.value = pick.value;
            $input.dispatchEvent(new Event('input', {bubbles: true}));
            $input.dispatchEvent(new Event('change', {bubbles: true}));
        }
    });
}
