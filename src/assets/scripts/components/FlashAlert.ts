window.customElements.get('flash-alert') || window.customElements.define('flash-alert', class extends HTMLElement {
    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        const $alert = this.querySelector('.alert') as HTMLElement;
        const $close = $alert.querySelector('[data-close]') as HTMLElement;
        const type = $alert.dataset.alert!;

        const close = () => {
            $alert.classList.add('dismissed');
            $alert.ontransitionend = () => this.remove();
        }

        if ($close) {
            $close.onclick = () => close();
        }

        if (type === 'success') {
            const timer = setTimeout(close, 5000);
            $alert.onmouseenter = () => clearTimeout(timer);
        }
    }
});