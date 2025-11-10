import collapse from "./includes/collapse";
import dropdown from "./includes/dropdown";
import filter from "./includes/filter";
import modal from "./includes/modals";
import tooltip from "./includes/tooltips";
import {toggleTargetsOnChange, updateTargetsOnChange} from "./includes/forms";

import htmx from "htmx.org"

const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).getAttribute('content') as string;

const queryAll = (selector: string, method: Function) => {
    document.querySelectorAll(selector).forEach(($el: Element) => method($el));
};

htmx.on('htmx:configRequest', (event: Event) => {
    (event as CustomEvent).detail.headers['X-CSRF-Token'] = csrfToken;
});

htmx.on('htmx:load', () => {
    queryAll('[data-collapse]', collapse);
    queryAll('[data-dropdown]', dropdown);
    queryAll('[data-filter]', filter);
    queryAll('[data-form-target]', updateTargetsOnChange);
    queryAll('[data-form-toggle]', toggleTargetsOnChange);
    queryAll('[data-modal]', modal);
    queryAll('[data-tooltip]', tooltip);
});


htmx.on('htmx:responseError', (event: Event) => {
    const detail = (event as CustomEvent).detail;

    if (!detail.xhr.ok) {
        alert(detail.xhr.statusText);
    }
});