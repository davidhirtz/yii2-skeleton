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

        const upload = (file: File, start: number, end: number): Promise<Response> => {
            const body = new FormData();
            const headers: HeadersInit = new Headers();

            headers.set('X-CSRF-Token', csrfToken);

            if (start > 0 || end < file.size) {
                const blob = file.slice(start, end);
                body.append($input.name, new File([blob], file.name, {type: file.type}));
                headers.set('Content-Range', `bytes ${start}-${end - 1}/${file.size}`);
            } else {
                body.append($input.name, file);
            }

            return fetch(this.dataset.url as string, {
                body: body,
                headers: headers,
                method: 'POST',
            });
        }

        $input.addEventListener('change', async event => {
            const files = (event.target as HTMLInputElement).files;

            if (!files) {
                return;
            }

            $btn.disabled = true;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const totalChunks = Math.ceil(file.size / chunkSize);

                for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                    const start = chunkIndex * chunkSize;
                    const end = Math.min(start + chunkSize, file.size);

                    await upload(file, start, end).then(response => {
                        // Todo check multiple files and only update once all are done
                        // Add progress
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
                    });
                }
            }

            $btn.disabled = false;
        });

        $btn.onclick = () => $input.click();
    }
});