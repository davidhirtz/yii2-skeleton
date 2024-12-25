import {intlFormatDistance} from 'date-fns'

window.customElements.define('x-timeago', class extends HTMLElement {
    timeout: number | null = null;
    date: Date;

    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        this.date = new Date(this.dataset.datetime);
        console.log('connectedCallback', this.date);

        if (!this.title) {
            const locale = this.dataset.locale || document.documentElement.lang || new Intl.DateTimeFormat().resolvedOptions().locale;
            this.title = this.date.toLocaleString(locale);
        }

        this.setRelativeTime();

        this.onclick = () => {
            this.remove();
        }
    }

    // noinspection JSUnusedGlobalSymbols
    disconnectedCallback() {
        clearTimeout(this.timeout);
    }

    setRelativeTime() {
        const now = new Date();
        const diff = Math.abs(now.getTime() - this.date.getTime());

        this.textContent = intlFormatDistance(this.date, now, {
            locale: document.documentElement.lang,
        });

        this.timeout = setTimeout(() => this.setRelativeTime(), diff < 60000 ? 1000 : 60000);
    }
});