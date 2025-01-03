import {Dropdown, Tooltip} from 'bootstrap';
import "htmx.org";

const doc = document;
const csrfToken = doc.querySelector('meta[name="csrf-token"]').getAttribute('content');

doc.body.addEventListener('htmx:configRequest', (event: CustomEvent) => {
    event.detail.headers['X-CSRF-Token'] = csrfToken;
});

doc.body.addEventListener('htmx:load', () => {

    doc.querySelectorAll('.dropdown-toggle').forEach(($el: Element) => new Dropdown($el));

    doc.querySelectorAll('[data-modal]').forEach(($el: HTMLElement) => $el.addEventListener('click', () => {
        const selector = $el.dataset.modal;
        const $target = selector
            ? (doc.querySelector(selector) as HTMLElement)
            : $el.closest('[open]');

        if ($target) {
            $target[selector ? 'showModal' : 'close']();
        }
    }));

    doc.querySelectorAll('[data-toggle="tooltip"]').forEach(($el: Element) => new Tooltip($el));
});


// document.addEventListener('click', (event: MouseEvent) => {
//     const link = (event.target as HTMLElement).closest('a');
//
//     if(link) {
//         const method = link.dataset.method;
//         const message = link.dataset.confirm;
//         const form = link.dataset.form;
//
//         if(link.hasAttribute('data-method')) {
//             event.preventDefault();
//             const method = link.getAttribute('data-method');
//             if(method === 'delete') {
//                 if(confirm('Are you sure?')) {
//                     fetch(link.href, { method: 'DELETE', headers: { 'X-CSRF-Token': csrfToken } })
//                         .then(response => {
//                             if(response.ok) {
//                                 link.closest('tr')?.remove();
//                             }
//                         });
//                 }
//             }
//         }
//     }
// });