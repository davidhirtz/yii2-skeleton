import htmx from 'htmx.org'

import onLoad from './includes/onLoad';
// The extension is an IIFE reaching for the global `htmx`, which the ESM build of the import above assigns —
// so the order of these two lines is what makes it register at all.
import 'htmx.org/dist/ext/hx-head.js';
import type {TinyMCE} from 'tinymce';
import 'x-timeago';

import {asidePin} from './includes/aside';
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

onLoad(($container) => {
    const queryAll = (selector: string, method: Function) => {
        ($container as HTMLElement).querySelectorAll(selector).forEach(($el: Element) => method($el));
    };

    queryAll('[data-aside-pin]', asidePin);
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
    queryAll('[data-timezone-offset]', timezone);

    queryAll('[aria-invalid]', ($input: HTMLElement) => {
        $input.addEventListener('input', () => $input.removeAttribute('aria-invalid'));
    });
});


htmx.on('htmx:response:error', (event: Event) => {
    const {ctx} = (event as CustomEvent).detail;

    const iframe = document.createElement('iframe');

    iframe.style.width = '100%';
    iframe.style.height = '100%';
    iframe.style.border = '0';
    iframe.srcdoc = ctx.text;

    const $dialog = createModal(`${ctx.response.status} ${ctx.response.raw.statusText}`, iframe.outerHTML);

    $dialog.style.width = 'min(90rem, 90vw)';
    $dialog.style.height = 'min(60rem, 90vh)';
});

// `hx-include` serializes the form without firing `submit`, which is when TinyMCE writes to its textarea. htmx
// collects the values inside its own listener for the triggering event and fires no event of its own before
// that — `htmx:config:request` is already too late — so the editors are flushed in the capture phase of the
// three events htmx listens for.
['submit', 'click', 'change'].forEach((name: string) => {
    document.addEventListener(name, () => window.tinymce?.triggerSave(), true);
});

// A save lands back on the page it was made on, where an instant jump to the top reads as a glitch and a scroll
// reads as the page answering. Navigation keeps the jump, so the new page does not scroll past its own header.
// `show:top` is a bare `scrollIntoView(true)`, so the choice is the document's own `scroll-behavior`.
htmx.on('htmx:before:swap', (event: Event) => {
    const method = (event as CustomEvent).detail.ctx?.request?.method;
    document.documentElement.style.scrollBehavior = method === 'POST' ? 'smooth' : 'auto';
});

// Only a swap of `#wrap` is a navigation, and `data-navigate` says so for the CSS. A narrower one — a grid's
// filter, sort or pager, an autocomplete list, a flash — leaves the page around it standing, and is `none`: the
// root cross-fade is what makes the swapped region morph rather than blink. The target decides this, never the
// response, which is the whole page either way whenever `hx-select` picked something out of it.
//
// Within a navigation, `#wrap` carries the depth the header computed, so comparing the incoming one with the
// current says whether the user went deeper or back up — which is the direction the header's subtitle slides.
htmx.on('htmx:before:swap', (event: Event) => {
    const {ctx} = (event as CustomEvent).detail;
    const $wrap = document.getElementById('wrap');

    if (ctx.target !== $wrap && ctx.target !== document.body) {
        document.documentElement.dataset.navigate = 'none';
        return;
    }

    const depth = typeof ctx.text === 'string' ? /data-depth="(\d+)"/.exec(ctx.text)?.[1] : undefined;
    const current = Number($wrap?.dataset.depth ?? 0);
    const incoming = depth === undefined ? current : Number(depth);

    document.documentElement.dataset.navigate = incoming === current
        ? 'same'
        : (incoming > current ? 'down' : 'up');
});

htmx.config.transitions = true;