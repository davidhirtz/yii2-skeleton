import {Dropdown, Modal, Tooltip} from 'bootstrap';
import "htmx.org";

const doc = document;
const csrfToken = doc.querySelector('meta[name="csrf-token"]').getAttribute('content');

doc.body.addEventListener('htmx:configRequest', (event: CustomEvent) => {
    event.detail.headers['X-CSRF-Token'] = csrfToken;
});

doc.querySelectorAll('.dropdown-toggle').forEach(($el: Element) => new Dropdown($el));

doc.querySelectorAll('[data-toggle="modal"]').forEach(($el: HTMLElement) => $el.addEventListener('click', (e) => {
    const $target = doc.querySelector($el.dataset.target);

    if ($target) {
        new Modal($target).show();
    }

    e.preventDefault();
}));

doc.querySelectorAll('[data-toggle="tooltip"]').forEach(($el: Element) => new Tooltip($el));


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