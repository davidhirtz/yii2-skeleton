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
    #editor: Editor[] = [];

    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        const $textarea = this.querySelector('textarea')!;
        const config = JSON.parse(this.dataset.config!);

        // TinyMCE requires a unique ID for each editor instance (issues with HTMX swaps)
        $textarea.id = `tinymce-${Math.random().toString(36).substring(2, 15)}`;

        setTimeout(async () => this.#editor = await tinymce.init({...config, selector: `#${$textarea!.id}`}), 1);
    }

    // noinspection JSUnusedGlobalSymbols
    disconnectedCallback() {
        this.#editor.forEach(editor => {
            editor.remove();
        });
    }
});