import htmx from "htmx.org"

import {startBusy, stopBusy} from "../includes/busy";

window.customElements.get('file-upload') || window.customElements.define('file-upload', class extends HTMLElement {
    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        const $input = this.querySelector('input[type="file"]') as HTMLInputElement | null;

        if (!$input) {
            return;
        }

        const $target = (this.dataset.target ? document.querySelector(this.dataset.target) : null) || document.body;
        const $btn = this.querySelector('button') as HTMLButtonElement;
        const chunkSize = this.dataset.chunkSize ? parseInt(this.dataset.chunkSize) : 1024 * 1024 * 2;

        $input.addEventListener('change', async event => {
            const files = (event.target as HTMLInputElement).files;

            if (!files) {
                return;
            }

            // The swap replaces the target, not the dropdown the button sits in, which would stay open behind it.
            const $popover = this.closest('[popover]') as HTMLElement | null;

            if ($popover?.matches(':popover-open')) {
                $popover.hidePopover();
            }

            let totalSize = 0;

            for (let fileIndex = 0; fileIndex < files.length; fileIndex++) {
                totalSize += files[fileIndex].size;
            }

            const $progress = startBusy(totalSize);

            try {
                await this.upload(files, $input, $target, chunkSize, $progress);
            } finally {
                stopBusy();
            }
        });

        $btn.onclick = () => $input.click();
    }

    async upload(files: FileList, $input: HTMLInputElement, $target: Element, chunkSize: number, $progress: HTMLProgressElement) {
        for (let fileIndex = 0; fileIndex < files.length; fileIndex++) {
            const file = files[fileIndex];
            const totalChunks = Math.ceil(file.size / chunkSize);

            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                const body = new FormData();
                const headers: HeadersInit = new Headers();
                const start = chunkIndex * chunkSize;
                const end = Math.min(start + chunkSize, file.size);

                if (start > 0 || end < file.size) {
                    const blob = file.slice(start, end);
                    body.append($input.name, new File([blob], file.name, {type: file.type}));
                    headers.set('Content-Range', `bytes ${start}-${end - 1}/${file.size}`);
                } else {
                    body.append($input.name, file);
                }

                if (fileIndex < files.length - 1) {
                    headers.set('Prefer', 'status=204');
                }

                headers.set('X-CSRF-Token', Object.values(JSON.parse(document.querySelector('#wrap')!.getAttribute('hx-headers:inherited') as string) as Object).pop());

                await fetch(this.dataset.url as string, {
                    body: body,
                    headers: headers,
                    method: 'POST',
                })
                    .then(response => {
                        if (response.status === 200) {
                            response.text().then(html => {
                                void htmx.swap({
                                    text: html,
                                    target: $target,
                                    swap: 'outerHTML show:top',
                                    select: this.dataset.target || undefined,
                                    // The swap is not an htmx request, so nothing inherits the body's
                                    // `hx-select-oob` and the flashes have to be named here.
                                    selectOOB: ['#flashes:beforeend', this.dataset.selectOob]
                                        .filter(Boolean)
                                        .join(','),
                                });
                            })
                        } else if (!response.ok) {
                            alert(response.statusText);
                            chunkIndex = totalChunks;
                        }

                        $progress.value += (end - start);
                    });
            }
        }
    }
});