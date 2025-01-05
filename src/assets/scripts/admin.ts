// import {Dropdown, Tooltip} from 'bootstrap';
import modal from "./components/modals";
import tooltip from "./components/tooltips";
import {toggleTargetsOnChange} from "./components/forms";
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
    // doc.querySelectorAll('.dropdown-toggle').forEach(($el: Element) => new Dropdown($el));
    // doc.querySelectorAll('[data-toggle="tooltip"]').forEach(($el: Element) => new Tooltip($el));

    // doc.querySelectorAll('[data-collapse]').forEach(($el: HTMLButtonElement) => $el.onclick = () => {
    //     const $target = doc.querySelector($el.dataset.collapse) as HTMLElement;
    //
    //     if ($target) {
    //         $target.ariaExpanded = $target.classList.toggle('d-none') ? 'false' : 'true';
    //     }
    // });

    queryAll('[data-modal]', modal);
    queryAll('[data-tooltip]', tooltip);
    queryAll('[data-form-toggle]', toggleTargetsOnChange);
});
