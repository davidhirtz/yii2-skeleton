/**
 * The alert comes from a template the server renders, so it looks like the server's own; a page without one (outside
 * the admin) still shows the message.
 */
export const showErrorFlash = (message: string) => {
    const $template = document.getElementById('error-flash') as HTMLTemplateElement | null;
    const $flashes = document.getElementById('flashes');
    const $flash = $template?.content.cloneNode(true) as DocumentFragment | undefined;
    const $content = $flash?.querySelector('.alert-content');

    if (!$flashes || !$flash || !$content) {
        alert(message);
        return;
    }

    $content.textContent = message;
    $flashes.append($flash);
};
