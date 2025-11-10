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

document.body.addEventListener('htmx:configRequest', (event: Event) => {
    (event as CustomEvent).detail.headers['X-CSRF-Token'] = csrfToken;
});

document.body.addEventListener('htmx:load', () => {
    queryAll('[data-collapse]', collapse);
    queryAll('[data-dropdown]', dropdown);
    queryAll('[data-filter]', filter);
    queryAll('[data-form-target]', updateTargetsOnChange);
    queryAll('[data-form-toggle]', toggleTargetsOnChange);
    queryAll('[data-modal]', modal);
    queryAll('[data-tooltip]', tooltip);
});

console.log(htmx);