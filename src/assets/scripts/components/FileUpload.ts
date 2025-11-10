const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).getAttribute('content') as string;

window.customElements.get('file-upload') || window.customElements.define('file-upload', class extends HTMLElement {
    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        const $input = this.querySelector('input[type="file"]') as HTMLInputElement | null;

        if (!$input) {
            return;
        }

        const chunkSize = this.dataset.chunkSize ? parseInt(this.dataset.chunkSize) : 1024 * 1024 * 2;

        const upload = async (file: File, start: number, end: number) => {
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

            await fetch(this.dataset.url as string, {
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

            console.log('Starting upload of', files.length, 'file(s)');

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const totalChunks = Math.ceil(file.size / chunkSize);

                for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                    const start = chunkIndex * chunkSize;
                    const end = Math.min(start + chunkSize, file.size);

                    await upload(file, start, end)
                        .then(() => {
                            console.log('Uploaded chunk', chunkIndex, 'of file', file.name);
                        })
                        .catch(error => {
                            console.error('Upload failed for chunk', chunkIndex, 'of file', file.name, error);
                            chunkIndex = totalChunks; // Exit the loop on error
                        });
                }
            }
        });
    }
});