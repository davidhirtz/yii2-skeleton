window.customElements.get('flash-alert') || window.customElements.define('flash-alert', class extends HTMLElement {
    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        const $alert = this.querySelector('.alert') as HTMLElement;
        const $close = $alert.querySelector('[data-close]') as HTMLElement;
        const type = $alert.dataset.alert!;

        const $siblings = this.parentElement!.children as HTMLCollectionOf<HTMLElement>;
        const max = window.innerWidth < 767 ? 3 : 5;

        const close = ($target: HTMLElement) => {
            $target.classList.add('dismissed');
            $target.ontransitionend = () => $target.remove();
        }

        if ($siblings.length >= max) {
            close($siblings[0]);
        }

        if ($close) {
            $close.onclick = () => close(this);
        }

        if (type === 'success') {
            const timer = setTimeout(() => close(this), 5000);
            $alert.onmouseenter = () => clearTimeout(timer);
        }
    }
});