import htmx from "htmx.org";

htmx.onLoad(($container) => {
    const $form = ($container as HTMLElement).querySelector('[data-id="signup"]') as HTMLFormElement;

    const getField = (id: string): HTMLInputElement => {
        return $form.querySelector(`[data-id="${id}"]`) as HTMLInputElement;
    }

    const $token = getField('token');
    const $honeypot = getField('honeypot');
    const $timeZone = getField('tz');

    $timeZone.value = new Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

    if (!$token.value) {
        const url = $token.dataset.url!;
        $token.removeAttribute('data-url');

        setTimeout(() => {
            fetch(url)
                .then(response => response.text())
                .then(data => $token.value = data)
                .then(() => $honeypot.value = '');
        }, 1000);
    }
});