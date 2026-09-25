// Password managers cancel the Enter key on forms with a password field, so the browser never submits them (#54).
// Catching the key on the document's capture phase comes before any listener on the form and its inputs; the form
// is then submitted the way the browser would have, validation and htmx included.
const IGNORED_TYPES = ['button', 'checkbox', 'color', 'file', 'image', 'radio', 'range', 'reset', 'submit'];

document.addEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key !== 'Enter' || event.isComposing || event.repeat || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
        return;
    }

    const $input = event.target;

    if (!($input instanceof HTMLInputElement) || IGNORED_TYPES.includes($input.type)) {
        return;
    }

    const $form = $input.form;

    if (!$form?.querySelector('input[type="password"]')) {
        return;
    }

    event.preventDefault();
    $form.requestSubmit();
}, true);
