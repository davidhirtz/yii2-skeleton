import "htmx.org"

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

document.body.addEventListener('htmx:configRequest', (event: CustomEvent) => {
    event.detail.headers['X-CSRF-Token'] = csrfToken;
});