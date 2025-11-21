import htmx from "htmx.org";

htmx.onLoad(($container) => {
    (($container as HTMLElement).querySelectorAll('[data-id="signup"]') as NodeListOf<HTMLFormElement>).forEach(($form) => {
        const getField = (id: string): HTMLInputElement => {
            return $form.querySelector(`[data-id="${id}"]`) as HTMLInputElement;
        }

        const $csrfToken = ($form.elements[0] as HTMLInputElement);
        const $token = getField('token');
        const $honeypot = getField('honeypot');
        const $timeZone = getField('tz');

        $timeZone.value = new Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

        if (!$token.value) {
            const url = $token.dataset.url!;
            const options: RequestInit = {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': $csrfToken.value
                }
            };

            $token.removeAttribute('data-url');

            setTimeout(() => {
                fetch(url, options)
                    .then(response => response.json())
                    .then(data => {
                        $csrfToken.value = data.csrf;
                        $token.value = data.token;
                        $honeypot.value = '';
                    });
            }, 1000);
        }
    });
});