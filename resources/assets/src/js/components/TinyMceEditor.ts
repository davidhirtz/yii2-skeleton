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

    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        const $textarea = this.querySelector('textarea')!;
        const config = JSON.parse(this.dataset.config!);

        // TinyMCE requires a unique ID for each editor instance (issues with HTMX swaps)
        $textarea.id = `tinymce-${Math.random().toString(36).substring(2, 15)}`;

        // The promise is kept so a disconnect within the timeout window still awaits and removes the editor, which a
        // drag between two positions does.
        this.#editors = new Promise((resolve) => {
            setTimeout(async () => resolve(await tinymce.init({...config, selector: `#${$textarea.id}`})), 1);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    async disconnectedCallback() {
        const editors = await this.#editors;
        this.#editors = null;

        editors?.forEach((editor) => {
            // Writes the content back to the textarea, so a move keeps what was typed.
            editor.save();
            editor.remove();
        });
    }
});