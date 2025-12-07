import htmx from "htmx.org"

import collapse from "./includes/collapse";
import dropdown from "./includes/dropdown";
import filter from "./includes/filter";
import modal from "./includes/modals";
import tooltip from "./includes/tooltips";
import {toggle, updateTargetsOnChange} from "./includes/forms";

import './components/FlashAlert';

htmx.onLoad(($container) => {
    const queryAll = (selector: string, method: Function) => {
        ($container as HTMLElement).querySelectorAll(selector).forEach(($el: Element) => method($el));
    };

    queryAll('[data-collapse]', collapse);
    queryAll('[data-dropdown]', dropdown);
    queryAll('[data-filter]', filter);
    queryAll('[data-form-target]', updateTargetsOnChange);
    queryAll('[data-toggle]', toggle);
    queryAll('[data-modal]', modal);
    queryAll('[data-tooltip]', tooltip);

    queryAll('[aria-invalid]', ($input: HTMLElement) => {
        $input.addEventListener('input', () => $input.removeAttribute('aria-invalid'));
    })
});


htmx.on('htmx:responseError', (event: Event) => {
    const detail = (event as CustomEvent).detail;

    if (!detail.xhr.ok) {
        alert(detail.xhr.statusText);
    }
});

htmx.config.historyCacheSize = 0;