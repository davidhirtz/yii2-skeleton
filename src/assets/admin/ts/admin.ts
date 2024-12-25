import {intlFormatDistance} from 'date-fns'

window.customElements.define('x-timeago', class extends HTMLElement {
    t: number | null = null;
    d: Date;
    l: string;

    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        try {
            this.d = new Date(this.dataset.datetime);
            this.l = this.dataset.locale || document.documentElement.lang || new Intl.DateTimeFormat().resolvedOptions().locale;
        } catch (e) {
            console.error(e);
            return;
        }

        if (!this.title) {
            this.title = this.d.toLocaleString(this.l);
        }

        this.set();
    }

    // noinspection JSUnusedGlobalSymbols
    disconnectedCallback() {
        clearTimeout(this.t);
    }

    set() {
        const now = new Date();
        const diff = Math.abs(now.getTime() - this.d.getTime());

        this.textContent = intlFormatDistance(this.d, now, {
            locale: this.l,
        });

        this.t = setTimeout(() => this.set(), diff < 60000 ? 1000 : 60000);
    }
});