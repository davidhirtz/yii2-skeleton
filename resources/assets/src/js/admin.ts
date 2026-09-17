import htmx from 'htmx.org'
import 'htmx-ext-head-support';
import type {TinyMCE} from 'tinymce';
import 'x-timeago';

import aside from './includes/aside';
import autocomplete from './includes/autocomplete';
import busy from './includes/busy';
import collapse from './includes/collapse';
import dropdown from './includes/dropdown';
import filter from './includes/filter';
import {closeModal, createModal} from './includes/modals';
import scrollActiveIntoView from './includes/scrollActiveIntoView';
import search from './includes/search';
import sticky from './includes/sticky';
import timezone from "./includes/timezone.ts";
import tooltip from './includes/tooltips';

import './includes/FlashAlert';

declare global {
    interface Window {
        tinymce?: TinyMCE;
    }
}

htmx.onLoad(($container) => {
    const queryAll = (selector: string, method: Function) => {
        ($container as HTMLElement).querySelectorAll(selector).forEach(($el: Element) => method($el));
    };

    queryAll('[data-aside]', aside);
    queryAll('[data-autocomplete]', autocomplete);
    queryAll('[data-busy]', busy);
    queryAll('[data-collapse]', collapse);
    queryAll('[popovertarget]', dropdown);
    queryAll('[data-filter]', filter);
    queryAll('[data-modal]', closeModal);
    queryAll('[data-scroll-active]', scrollActiveIntoView);
    queryAll('[data-search]', search);
    queryAll('[data-sticky]', sticky);
    queryAll('[data-tooltip]', tooltip);
    queryAll('[data-timezone-offset', timezone)

    queryAll('[aria-invalid]', ($input: HTMLElement) => {
        $input.addEventListener('input', () => $input.removeAttribute('aria-invalid'));
    });

    document.body.classList.remove('has-aside');
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

// `hx-include` serializes the form without firing `submit`, which is when TinyMCE writes to its textarea.
// `htmx:confirm` is the last event htmx fires before it reads the inputs — by `htmx:configRequest` the values
// have already been collected and a save would write the editor's content one request too late.
htmx.on('htmx:confirm', () => {
    window.tinymce?.triggerSave();
});

// A save lands back on the page it was made on, where an instant jump to the top reads as a glitch and a scroll
// reads as the page answering. Navigation keeps the jump, so the new page does not scroll past its own header.
htmx.on('htmx:beforeSwap', (event: Event) => {
    htmx.config.scrollBehavior = (event as CustomEvent).detail.requestConfig?.verb === 'post' ? 'smooth' : 'instant';
});

// The header cross-fades on every swap, and slides the way the navigation went: `#wrap` carries the depth the
// header computed, so comparing the incoming one with the current says whether the user went deeper or back up.
// A fragment that carries no `#wrap` — an autocomplete list, a flash — leaves the direction alone.
htmx.on('htmx:beforeSwap', (event: Event) => {
    const response = (event as CustomEvent).detail.serverResponse;
    const current = Number(document.getElementById('wrap')?.dataset.depth ?? 0);
    const depth = typeof response === 'string' ? /data-depth="(\d+)"/.exec(response)?.[1] : undefined;
    const incoming = depth === undefined ? current : Number(depth);

    document.documentElement.dataset.navigate = incoming === current
        ? 'none'
        : (incoming > current ? 'down' : 'up');
});

htmx.config.globalViewTransitions = true;
htmx.config.historyCacheSize = 0;