import htmx from "htmx.org"

import {startBusy, stopBusy} from "../includes/busy";
import {showErrorFlash} from "../includes/errorFlash";
import {showNetworkError} from "../includes/networkError";

// The upload was started from a button the user could see, so the target it refreshes almost always is on screen
// too — scrolling it to the top then throws the page around for no reason and reads as a reload. `show:top` is
// kept for the case it was added for: a target the swap would otherwise leave out of sight, such as a grid
// uploaded into from a dropdown far down the page.
const isOnScreen = ($el: Element): boolean => {
    const {top, bottom} = $el.getBoundingClientRect();
    return bottom > 0 && top < window.innerHeight;
};

export type FileUploadPrepareDetail = {
    file: File,
    input: HTMLInputElement,
    fields: Promise<[string, Blob] | null>[],
};

// HTTP/2 has no reason phrase, so the server sends its message in a header as well.
const getErrorMessage = (response: Response): string => {
    const message = response.headers.get('X-Upload-Error');
    return (message ? decodeURIComponent(message) : response.statusText) || String(response.status);
};

// A file dragged in from the desktop, not a row of a sortable grid.
const hasFiles = (event: DragEvent): boolean => !!event.dataTransfer?.types.includes('Files');

window.customElements.get('file-upload') || window.customElements.define('file-upload', class extends HTMLElement {
    private abortController: AbortController | null = null;

    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        const $input = this.querySelector('input[type="file"]') as HTMLInputElement | null;

        if (!$input) {
            return;
        }

        const $btn = this.querySelector('button') as HTMLButtonElement;
        const chunkSize = this.dataset.chunkSize ? parseInt(this.dataset.chunkSize) : 1024 * 1024 * 2;

        $input.addEventListener('change', async event => {
            const files = (event.target as HTMLInputElement).files;

            if (files) {
                await this.uploadFiles(Array.from(files), $input, chunkSize);
            }
        });

        $btn.onclick = () => $input.click();

        if (this.dataset.dropZone) {
            this.listenForDrops($input, chunkSize);
        }
    }

    // noinspection JSUnusedGlobalSymbols
    disconnectedCallback() {
        this.abortController?.abort();
        this.abortController = null;
    }

    async uploadFiles(files: File[], $input: HTMLInputElement, chunkSize: number) {
        if (!files.length) {
            return;
        }

        // The swap replaces the target, not the dropdown the button sits in, which would stay open behind it.
        const $popover = this.closest('[popover]') as HTMLElement | null;

        if ($popover?.matches(':popover-open')) {
            $popover.hidePopover();
        }

        const $progress = startBusy(files.reduce((total, file) => total + file.size, 0));

        try {
            await this.upload(files, $input, chunkSize, $progress);
        } finally {
            stopBusy();

            // Picking the same file again after a failure would otherwise fire no `change`.
            $input.value = '';
        }
    }

    // The listeners sit on the document because the zone is replaced by every swap. `dragenter` and `dragleave` fire
    // for every element crossed, the new one's `dragenter` before the old one's `dragleave`, so only a count of them
    // tells when the pointer has left the zone, or the window.
    listenForDrops($input: HTMLInputElement, chunkSize: number) {
        this.abortController = new AbortController();
        const signal = this.abortController.signal;

        let depth = 0;
        let $over: EventTarget | null = null;

        const getDropZone = (): HTMLElement | null => document.querySelector(this.dataset.dropZone as string);

        const isInDropZone = ($target: EventTarget | null): boolean => {
            const $zone = getDropZone();
            return !!$zone && $target instanceof Node && $zone.contains($target);
        };

        const update = () => getDropZone()?.classList.toggle('is-drop-target', depth > 0 && isInDropZone($over));

        document.addEventListener('dragenter', (event: DragEvent) => {
            if (hasFiles(event)) {
                depth++;
                $over = event.target;
                update();
            }
        }, {signal});

        document.addEventListener('dragleave', (event: DragEvent) => {
            if (hasFiles(event)) {
                depth = Math.max(0, depth - 1);
                update();
            }
        }, {signal});

        document.addEventListener('dragover', (event: DragEvent) => {
            if (hasFiles(event) && isInDropZone(event.target)) {
                event.preventDefault();
                event.dataTransfer!.dropEffect = 'copy';
            }
        }, {signal});

        document.addEventListener('drop', (event: DragEvent) => {
            depth = 0;
            update();

            if (!hasFiles(event) || !isInDropZone(event.target)) {
                return;
            }

            event.preventDefault();

            // A dropped folder arrives as an empty file that cannot be read.
            const files = Array.from(event.dataTransfer!.items)
                .filter(item => item.kind === 'file' && item.webkitGetAsEntry?.()?.isDirectory !== true)
                .map(item => item.getAsFile())
                .filter((file): file is File => !!file);

            void this.uploadFiles($input.multiple ? files : files.slice(0, 1), $input, chunkSize);
        }, {signal});
    }

    // Looked up for every swap: the swap replaces the target, and a button outside it — the header, a dropdown — would
    // otherwise keep swapping into the detached element, so only the first upload showed up in the grid.
    getTarget(): Element {
        return (this.dataset.target ? document.querySelector(this.dataset.target) : null) || document.body;
    }

    async upload(files: File[], $input: HTMLInputElement, chunkSize: number, $progress: HTMLProgressElement) {
        for (let fileIndex = 0; fileIndex < files.length; fileIndex++) {
            const file = files[fileIndex];
            const totalChunks = Math.ceil(file.size / chunkSize);

            // A bundle's script adds fields to the request completing the file (`yii2-media-video` a frame of the
            // video), prepared while the chunks before it upload.
            const detail: FileUploadPrepareDetail = {file, input: $input, fields: []};

            // Keys the server's partial file, which two uploads of the same filename would otherwise share.
            const uploadId = Math.random().toString(36).slice(2) + Date.now().toString(36);
            this.dispatchEvent(new CustomEvent('file-upload:prepare', {bubbles: true, detail}));

            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                const body = new FormData();
                const headers: HeadersInit = new Headers();
                const start = chunkIndex * chunkSize;
                const end = Math.min(start + chunkSize, file.size);

                if (start > 0 || end < file.size) {
                    const blob = file.slice(start, end);
                    body.append($input.name, new File([blob], file.name, {type: file.type}));
                    headers.set('Content-Range', `bytes ${start}-${end - 1}/${file.size}`);
                    headers.set('X-Upload-Id', uploadId);
                } else {
                    body.append($input.name, file);
                }

                if (chunkIndex === totalChunks - 1) {
                    for (const field of await Promise.all(detail.fields)) {
                        if (field) {
                            body.append(field[0], field[1]);
                        }
                    }
                }

                if (fileIndex < files.length - 1) {
                    headers.set('Prefer', 'status=204');
                }

                headers.set('X-CSRF-Token', Object.values(JSON.parse(document.querySelector('#wrap')!.getAttribute('hx-headers:inherited') as string) as Object).pop());

                let response: Response;

                try {
                    response = await fetch(this.dataset.url as string, {
                        body: body,
                        headers: headers,
                        method: 'POST',
                    });
                } catch {
                    showNetworkError();
                    return;
                }

                if (response.status === 200) {
                    const html = await response.text();
                    const $target = this.getTarget();

                    // A field swapped in by an upload changes its form without an `input` or `change` of its own.
                    const $form = $target.closest('form');

                    void Promise.resolve(htmx.swap({
                        text: html,
                        target: $target,
                        swap: isOnScreen($target) ? 'outerHTML' : 'outerHTML show:top',
                        select: this.dataset.target || undefined,
                        // The swap is not an htmx request, so nothing inherits the body's
                        // `hx-select-oob` and the flashes have to be named here.
                        selectOOB: ['#flashes:beforeend', this.dataset.selectOob]
                            .filter(Boolean)
                            .join(','),
                    })).then(() => $form?.dispatchEvent(new Event('change', {bubbles: true})));
                } else if (!response.ok) {
                    showErrorFlash(getErrorMessage(response));
                    break;
                }

                $progress.value += (end - start);
            }
        }
    }
});