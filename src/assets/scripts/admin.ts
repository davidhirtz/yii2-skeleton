import dropdown from "./components/dropdown";
import modal from "./components/modals";
import tooltip from "./components/tooltips";
import {toggleTargetsOnChange, updateTargetsOnChange} from "./components/forms";

import "htmx.org";

const doc = document;
const csrfToken = doc.querySelector('meta[name="csrf-token"]').getAttribute('content');

const queryAll = (selector: string, method: Function) => {
    doc.querySelectorAll(selector).forEach(($el: Element) => method($el));
};

doc.body.addEventListener('htmx:configRequest', (event: CustomEvent) => {
    event.detail.headers['X-CSRF-Token'] = csrfToken;
});

doc.body.addEventListener('htmx:load', () => {
    queryAll('[data-dropdown]', dropdown);
    queryAll('[data-modal]', modal);
    queryAll('[data-tooltip]', tooltip);
    queryAll('[data-form-target]', updateTargetsOnChange);
    queryAll('[data-form-toggle]', toggleTargetsOnChange);
});
