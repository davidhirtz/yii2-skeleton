import tinymce, {Editor} from 'tinymce';

import 'tinymce/icons/default/icons.min.js';

import 'tinymce/themes/silver/theme.min.js';
import 'tinymce/models/dom/model.min.js';

import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/table';

window.customElements.get('tinymce-editor') || window.customElements.define('tinymce-editor', class extends HTMLElement {
    #editors: Promise<Editor[]> | null = null;
    #removed: Promise<void> = Promise.resolve();

    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        const $textarea = this.querySelector('textarea')!;
        const config = JSON.parse(this.dataset.config!);

        // TinyMCE requires a unique ID for each editor instance (issues with HTMX swaps). A new one per connect also
        // tells a stale initialisation, still waiting for its timer when the element moved again, to stand down.
        const id = `tinymce-${Math.random().toString(36).substring(2, 15)}`;
        $textarea.id = id;

        const removed = this.#removed;

        // A move disconnects and reconnects in one go, so the editor of the old position is removed first: its
        // removal restores the textarea, which would otherwise show beside the new editor.
        this.#editors = new Promise((resolve) => {
            setTimeout(async () => {
                await removed;

                if ($textarea.id !== id || !this.isConnected) {
                    resolve([]);
                    return;
                }

                const editors = await tinymce.init({
                    ...config,
                    selector: `#${id}`,
                    // The iframe's document follows the admin's colour scheme, not only the browser's.
                    setup: (editor: Editor) => editor.on('PreInit', () => {
                        const theme = document.documentElement.dataset.theme;

                        if (theme) {
                            editor.getDoc().documentElement.dataset.theme = theme;
                        }
                    }),
                });

                // Typing happens in the editor's iframe, so the form learns of it from the textarea it stands for.
                editors.forEach((editor) => editor.on('input change undo redo', () => {
                    $textarea.dispatchEvent(new Event('input', {bubbles: true}));
                }));

                resolve(editors);
            }, 1);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    disconnectedCallback() {
        // Taken synchronously: a move runs `connectedCallback()` before any await here would resume.
        const editors = this.#editors;
        this.#editors = null;

        this.#removed = (async () => {
            (await editors)?.forEach((editor) => {
                // Writes the content back to the textarea, so a move keeps what was typed.
                editor.save();
                editor.remove();
            });
        })();
    }
});
