

window.customElements.get('tinymce-editor') || window.customElements.define('tinymce-editor', class extends HTMLElement {
    #id: string | undefined;

    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        const textarea = this.querySelector('textarea');
        const config = JSON.parse(this.dataset.config!);

        config.selector = this.#id = `#${textarea!.id}`;
        setTimeout(() => tinymce.init(config), 100);
        this.style.height = `${config.height!}px`;
    }

    // noinspection JSUnusedGlobalSymbols
    disconnectedCallback() {
        tinymce.remove(this.#id);
    }
});