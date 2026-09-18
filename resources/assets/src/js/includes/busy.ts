// Fetching a file from a URL or storing an upload runs for seconds with nothing on screen to say so. The page is
// made `inert` rather than only dimmed, so nothing can be submitted a second time behind the first, and an open
// modal is closed: a dialog sits in the top layer, above both the overlay and the bar.
const $progress = document.createElement('progress');
$progress.className = 'progress';

// An operation that is over within a few frames must paint nothing at all: a full-page overlay and a bar that
// appear and are gone inside a tenth of a second read as the page having reloaded, which is what a small upload
// looked like. `inert` is still applied at once — it is invisible, and it is what stops a second submit.
const PAINT_DELAY = 200;

let depth = 0;
let timeout = 0;

export const startBusy = (total = 0): HTMLProgressElement => {
    (document.querySelector('dialog[open]') as HTMLDialogElement | null)?.close();

    // Without a value the bar is indeterminate, which is all a request of unknown length can say.
    total ? ($progress.max = total, $progress.value = 0) : $progress.removeAttribute('value');

    if (!depth++) {
        document.body.inert = true;

        timeout = window.setTimeout(() => {
            document.documentElement.classList.add('is-busy');
            document.body.appendChild($progress);
        }, PAINT_DELAY);
    }

    return $progress;
}

export const stopBusy = () => {
    if (depth && !--depth) {
        window.clearTimeout(timeout);
        document.body.inert = false;
        document.documentElement.classList.remove('is-busy');
        $progress.remove();
    }
}

export default ($el: HTMLElement) => {
    $el.addEventListener('htmx:before:request', () => startBusy());
    $el.addEventListener('htmx:after:request', () => stopBusy());
}
