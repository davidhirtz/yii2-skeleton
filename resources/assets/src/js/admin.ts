import htmx from 'htmx.org'
import 'x-timeago';

import collapse from './includes/collapse';
import dropdown from './includes/dropdown';
import filter from './includes/filter';
import {closeModal, createModal} from './includes/modals';
import tooltip from './includes/tooltips';
import {toggle, updateTargetsOnChange} from './includes/forms';

import './includes/FlashAlert';

htmx.onLoad(($container) => {
    const queryAll = (selector: string, method: Function) => {
        ($container as HTMLElement).querySelectorAll(selector).forEach(($el: Element) => method($el));
    };

    queryAll('[data-collapse]', collapse);
    queryAll('[data-dropdown]', dropdown);
    queryAll('[data-filter]', filter);
    queryAll('[data-form-target]', updateTargetsOnChange);
    queryAll('[data-toggle]', toggle);
    queryAll('[data-modal]', closeModal);
    queryAll('[data-tooltip]', tooltip);

    queryAll('[aria-invalid]', ($input: HTMLElement) => {
        $input.addEventListener('input', () => $input.removeAttribute('aria-invalid'));
    })
});


htmx.on('htmx:responseError', (event: Event) => {
    const detail = (event as CustomEvent).detail;
    const xhr = detail.xhr;

    if (xhr.ok) {
        return;
    }

    const iframe = document.createElement('iframe');

    iframe.style.width = '100%';
    iframe.style.height = '100%';
    iframe.style.border = '0';
    iframe.srcdoc = xhr.responseText;

    const $dialog = createModal(`${xhr.status} ${xhr.statusText}`, iframe.outerHTML);

    $dialog.style.width = 'min(90rem, 90vw)';
    $dialog.style.height = 'min(60rem, 90vh)';
});

htmx.config.historyCacheSize = 0;