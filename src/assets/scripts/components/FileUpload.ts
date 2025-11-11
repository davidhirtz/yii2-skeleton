import htmx from "htmx.org"

const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).getAttribute('content') as string;

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
        const minProgressSize = chunkSize * 3;

        $input.addEventListener('change', async event => {
            const files = (event.target as HTMLInputElement).files;

            if (!files) {
                return;
            }

            let totalSize = 0;

            for (let fileIndex = 0; fileIndex < files.length; fileIndex++) {
                totalSize += files[fileIndex].size;
            }

            const $progress = totalSize > minProgressSize ? document.createElement('progress') : null;

            if ($progress) {
                $progress.className = 'progress';
                $progress.max = totalSize;
                $progress.value = 0;
                $target.appendChild($progress);
            }

            $btn.disabled = true;

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

                    headers.set('X-CSRF-Token', csrfToken);

                    await fetch(this.dataset.url as string, {
                        body: body,
                        headers: headers,
                        method: 'POST',
                    })
                        .then(response => {
                            if (response.status === 200) {
                                response.text().then(html => {
                                    htmx.swap($target, html, {
                                        swapStyle: 'outerHTML',
                                        swapDelay: 0,
                                        settleDelay: 0,
                                        show: 'top',
                                    }, {
                                        select: this.dataset.target || undefined,
                                    });
                                })
                            } else if (!response.ok) {
                                alert(response.statusText);
                                chunkIndex = totalChunks;
                            }

                            if ($progress) {
                                $progress.value += (end - start);
                            }
                        });
                }
            }

            $btn.disabled = false;
        });

        $btn.onclick = () => $input.click();
    }
});