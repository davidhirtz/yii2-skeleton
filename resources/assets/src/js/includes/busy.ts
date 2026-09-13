// Fetching a file from a URL or storing an upload runs for seconds with nothing on screen to say so. The page is
// made `inert` rather than only dimmed, so nothing can be submitted a second time behind the first, and an open
// modal is closed: a dialog sits in the top layer, above both the overlay and the bar.
const $progress = document.createElement('progress');
$progress.className = 'progress';

let depth = 0;

export const startBusy = (total = 0): HTMLProgressElement => {
    (document.querySelector('dialog[open]') as HTMLDialogElement | null)?.close();

    // Without a value the bar is indeterminate, which is all a request of unknown length can say.
    total ? ($progress.max = total, $progress.value = 0) : $progress.removeAttribute('value');

    if (!depth++) {
        document.documentElement.classList.add('is-busy');
        document.body.appendChild($progress);
        document.body.inert = true;
    }

    return $progress;
}

export const stopBusy = () => {
    if (depth && !--depth) {
        document.body.inert = false;
        document.documentElement.classList.remove('is-busy');
        $progress.remove();
    }
}

export default ($el: HTMLElement) => {
    $el.addEventListener('htmx:beforeRequest', () => startBusy());
    $el.addEventListener('htmx:afterRequest', () => stopBusy());
}
