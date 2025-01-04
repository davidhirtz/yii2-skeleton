import {Dropdown, Tooltip} from 'bootstrap';
import modals from "./components/modals";
import {toggleTargetsOnChange} from "./components/forms";
import "htmx.org";

const doc = document;
const csrfToken = doc.querySelector('meta[name="csrf-token"]').getAttribute('content');

doc.body.addEventListener('htmx:configRequest', (event: CustomEvent) => {
    event.detail.headers['X-CSRF-Token'] = csrfToken;
});

doc.body.addEventListener('htmx:load', () => {
    doc.querySelectorAll('.dropdown-toggle').forEach(($el: Element) => new Dropdown($el));
    doc.querySelectorAll('[data-toggle="tooltip"]').forEach(($el: Element) => new Tooltip($el));

    modals(doc.querySelectorAll('[data-modal]'));

    toggleTargetsOnChange(doc.querySelectorAll('[data-form-toggle]'));
});
