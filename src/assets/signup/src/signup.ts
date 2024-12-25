export default (selector: string) => {
    const $form = document.querySelector(selector) as HTMLFormElement;
    const $token = $form.querySelector('#token') as HTMLInputElement;
    const $honeypot = $form.querySelector('#honeypot') as HTMLInputElement;
    const $timeZone = $form.querySelector('#tz') as HTMLInputElement;

    $timeZone.value = new Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

    $form.onsubmit = (e: SubmitEvent) => {
        if (!$token.value) {
            fetch($token.dataset.url)
                .then(response => response.text())
                .then(data => $token.value = data)
                .then(() => {
                    $honeypot.value = '';
                    ($form).submit()
                });

            e.preventDefault();
        }
    };
}