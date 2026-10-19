// First, so that it hears what every module below throws while it is evaluated.
import './includes/scriptError';
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
import filter from './includes/filter';
import {closeModal, createModal} from './includes/modals';
import scrollActiveIntoView from './includes/scrollActiveIntoView';
import search from './includes/search';
import selectLink from './includes/selectLink';
import sticky, {jump} from './includes/sticky';
import timezone from "./includes/timezone.ts";
import tooltip from './includes/tooltips';
import networkError from './includes/networkError';
import {rememberFocus, restoreFocus} from './includes/focus';
import {confirmLeaving, hasUnsavedChanges, trackUnsavedForm} from './includes/unsaved';
import translationLayout, {initTranslationTabs} from './includes/translationLayout';
import staleSave from './includes/staleSave';
import configureHtmx from './includes/htmxConfig';
import characterCounter from './includes/characterCounter';

import './includes/dropdown';
import './includes/FlashAlert';
import './includes/submitOnEnter';

declare global {
    interface Window {
        tinymce?: TinyMCE;
    }
}

onLoad(($container) => {
    // A form swapped in by a failed save is the container itself, so it is asked too. An element's error is reported,
    // never thrown: it would leave every element after it unwired (monorepo issue #559).
    const queryAll = (selector: string, method: Function) => {
        const $elements = [...$container.querySelectorAll(selector)];

        if ($container.matches(selector)) {
            $elements.unshift($container);
        }

        $elements.forEach(($el: Element) => {
            try {
                method($el);
            } catch (error) {
                reportError(error);
            }
        });
    };

    queryAll('[data-aside-pin]', asidePin);
    queryAll('[data-autocomplete]', autocomplete);
    queryAll('[data-busy]', busy);
    queryAll('[data-collapse]', collapse);
    queryAll('[data-filter]', filter);
    queryAll('[data-modal]', closeModal);
    queryAll('[data-scroll-active]', scrollActiveIntoView);
    queryAll('[data-search]', search);
    queryAll('[data-select-link]', selectLink);
    queryAll('[data-sticky]', sticky);
    queryAll('[data-tooltip]', tooltip);
    queryAll('form[data-unsaved]', trackUnsavedForm);
    queryAll('form[data-translation-layout]', translationLayout);
    queryAll('[data-translation-toolbar]', initTranslationTabs);
    queryAll('form[data-stale-save]', staleSave);
    queryAll('[data-character-counter]', characterCounter);
    queryAll('[data-timezone-offset]', timezone);

    // A search renders its term back, and the caret belongs behind it.
    queryAll('[data-caret-end]', ($input: HTMLInputElement) => {
        $input.addEventListener('focus', () => $input.setSelectionRange($input.value.length, $input.value.length));
    });

    queryAll('[aria-invalid]', ($input: HTMLElement) => {
        $input.addEventListener('input', () => $input.removeAttribute('aria-invalid'));
    });
});


// htmx 4 swaps an error response like any other and pushes a boosted form's URL, so a 500 emptied the page behind
// the modal (the exception page has no `#wrap` for `hx-select`) and left the POST's URL in the address bar.
// `htmx:after:request` is the last cancelable event before both; every `HX-*` header the server sends comes with
// a 200, so nothing an error response carries is lost by stopping here.
htmx.on('htmx:after:request', (event: Event) => {
    const {ctx} = (event as CustomEvent).detail;

    if (ctx.response.status < 400) {
        return;
    }

    event.preventDefault();

    const iframe = document.createElement('iframe');

    iframe.style.width = '100%';
    iframe.style.height = '100%';
    iframe.style.border = '0';
    iframe.srcdoc = ctx.text;

    const $dialog = createModal(`${ctx.response.status} ${ctx.response.raw.statusText}`, iframe.outerHTML);

    $dialog.style.width = 'min(90rem, 90vw)';
    $dialog.style.height = 'min(60rem, 90vh)';
});

htmx.on('htmx:error', networkError);

htmx.on('htmx:before:request', (event: Event) => {
    if (!confirmLeaving((event as CustomEvent).detail.ctx)) {
        event.preventDefault();
        return;
    }

    rememberFocus();
});

window.addEventListener('beforeunload', (event: BeforeUnloadEvent) => {
    if (hasUnsavedChanges()) {
        event.preventDefault();
    }
});

htmx.on('htmx:after:swap', (event: Event) => {
    restoreFocus((event as CustomEvent).detail.ctx);
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
// htmx jumps inside the view transition, where the named regions would morph from their scrolled positions
// (monorepo issue #550), so the jump is made before the old page is captured.
htmx.on('htmx:before:swap', (event: Event) => {
    const {ctx, tasks} = (event as CustomEvent).detail;
    const method = ctx?.request?.method;
    document.documentElement.style.scrollBehavior = method === 'POST' ? 'smooth' : 'auto';

    if (method === 'POST') {
        return;
    }

    const task = (tasks as {type: string; target: Element | string | null; swapSpec?: {show?: string; showTarget?: string}}[])
        .find((task) => task.type === 'main' && task.swapSpec?.show === 'top' && !task.swapSpec.showTarget);
    const $target = typeof task?.target === 'string' ? document.querySelector(task.target) : task?.target;

    if ($target && $target.getBoundingClientRect().top < 0) {
        jump($target);
    }
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

configureHtmx(htmx.config);