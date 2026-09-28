/**
 * A request that never got an answer — offline, the server unreachable — ends in `htmx:error` without a response,
 * and nothing on the page would say that the save did not happen. `fetch()` rejects with a `TypeError` then; an
 * `AbortError` is htmx cancelling a request it replaced, which is not the user's business.
 *
 * The flash is rendered by the server into a template, since a script has no translations.
 */
export default (event: Event) => {
    const {ctx, error} = (event as CustomEvent).detail ?? {};

    if (!ctx || ctx.response || !(error instanceof TypeError)) {
        return;
    }

    const $template = document.getElementById('network-error-flash') as HTMLTemplateElement | null;
    const $flashes = document.getElementById('flashes');

    if ($template && $flashes) {
        $flashes.append($template.content.cloneNode(true));
    }
};
