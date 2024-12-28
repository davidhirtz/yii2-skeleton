import "htmx.org"

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

document.body.addEventListener('htmx:configRequest', (event: CustomEvent) => {
    event.detail.headers['X-CSRF-Token'] = csrfToken;
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