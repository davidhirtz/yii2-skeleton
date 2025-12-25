import tinymce from 'tinymce';

import 'tinymce/icons/default/icons.min.js';

import 'tinymce/themes/silver/theme.min.js';
import 'tinymce/models/dom/model.min.js';

import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/table';

window.customElements.get('tinymce-editor') || window.customElements.define('tinymce-editor', class extends HTMLElement {
    #id: string | undefined;

    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        const textarea = this.querySelector('textarea');
        const config = JSON.parse(this.dataset.config!);

        console.log('TinyMceEditor connectedCallback', config);

        this.#id = `#${textarea!.id}`;
        void tinymce.init({...config, selector: this.#id});
    }

    // noinspection JSUnusedGlobalSymbols
    disconnectedCallback() {
        tinymce.remove(this.#id!);
    }
});