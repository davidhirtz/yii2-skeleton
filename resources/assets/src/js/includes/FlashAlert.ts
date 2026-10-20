const TIMEOUT = 5000;

let batch: HTMLElement[] = [];

export const close = ($target: HTMLElement) => {
    if ($target.classList.contains('dismissed')) {
        return;
    }

    // A height the transition can start from: `auto` does not animate.
    $target.style.height = `${$target.offsetHeight}px`;
    void $target.offsetHeight;
    $target.style.height = '0';
    $target.classList.add('dismissed');

    // Waits for every transition the class started, and for none when there are none (reduced motion, never painted).
    const animations = $target.getAnimations?.({subtree: true}) ?? [];
    void Promise.allSettled(animations.map((animation) => animation.finished)).then(() => $target.remove());
};

/**
 * Alerts connected in one task arrived in one response. Those the limit keeps get `--flash-index` before anything is
 * measured (reading a height starts every entrance at once), the rest are removed unpainted.
 */
const flush = ($container: HTMLElement) => {
    const max = window.innerWidth < 767 ? 3 : 5;
    const $entering = batch.filter(($flash) => $flash.isConnected);

    batch = [];

    $entering.slice(0, Math.max(0, $entering.length - max)).forEach(($flash) => $flash.remove());
    $entering.slice(-max).forEach(($flash, index) => $flash.style.setProperty('--flash-index', String(index)));

    const $live = [...$container.children as HTMLCollectionOf<HTMLElement>]
        .filter(($flash) => !$flash.classList.contains('dismissed'));

    $live.slice(0, Math.max(0, $live.length - max)).forEach(close);
};

window.customElements.get('flash-alert') || window.customElements.define('flash-alert', class extends HTMLElement {
    // noinspection JSUnusedGlobalSymbols
    connectedCallback() {
        const $alert = this.querySelector('.alert') as HTMLElement;
        const $close = $alert.querySelector('[data-close]') as HTMLElement;
        const type = $alert.dataset.alert!;
        const $container = this.parentElement!;

        if (batch.push(this) === 1) {
            queueMicrotask(() => flush($container));
        }

        if ($close) {
            $close.onclick = () => close(this);
        }

        if ('success' === type) {
            let timer: number;

            const start = () => timer = window.setTimeout(() => close(this), TIMEOUT);

            // Held while it is read, whether by pointer or keyboard.
            $alert.onmouseenter = () => clearTimeout(timer);
            $alert.onmouseleave = start;
            $alert.addEventListener('focusin', () => clearTimeout(timer));
            $alert.addEventListener('focusout', start);

            start();
        }
    }
});
