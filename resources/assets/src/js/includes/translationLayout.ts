/**
 * A form's languages as tabs: "All fields" shows every translated row (`data-language`) beneath the other, a
 * language button only that language's. The server names the project's default (`data-translation-layout`:
 * `inline` for every field, `tabs` for the first language); the choice is remembered per browser. Switching only
 * hides rows, so nothing typed is lost and every language still posts. A language holding a rejected field comes to
 * the front. The tabs sit inside the form or, placed by `FormContainer`, in a container of their own before it.
 */
const KEY = 'translationLanguage';
const ALL = 'all';

const read = (): string | null => {
    try {
        return window.localStorage.getItem(KEY);
    } catch {
        return null;
    }
};

const write = (value: string): void => {
    try {
        window.localStorage.setItem(KEY, value);
    } catch {
        // A private window or blocked storage keeps the choice for this page only.
    }
};

export default ($form: HTMLFormElement): void => {
    // Inside the form, or in a container of its own beside it, naming the form.
    const $toolbar = $form.querySelector<HTMLElement>('[data-translation-toolbar]')
        ?? document.querySelector<HTMLElement>(`[data-translation-toolbar="${CSS.escape($form.id)}"]`);
    const $all = $toolbar?.querySelector<HTMLButtonElement>('[data-translation-all]');
    const $languages = [...($toolbar?.querySelectorAll<HTMLButtonElement>('[data-translation-language]') ?? [])];

    if (!$toolbar || !$all || !$languages.length) {
        return;
    }

    const codes = $languages.map(($button) => $button.dataset.translationLanguage!);
    const fallback = $form.dataset.translationLayout === 'tabs' ? codes[0] : ALL;
    const $invalid = $form.querySelector<HTMLElement>('[data-language]:has([aria-invalid="true"])');

    let choice = read() ?? fallback;

    if (choice !== ALL && !codes.includes(choice)) {
        choice = fallback;
    }

    if ($invalid && choice !== ALL) {
        choice = $invalid.dataset.language!;
    }

    const apply = () => {
        // Read by the styles, which mark the labels of the translated fields while one language shows.
        $form.dataset.translationChoice = choice;

        [$all, ...$languages].forEach(($button) => {
            const isActive = ($button.dataset.translationLanguage ?? ALL) === choice;
            $button.classList.toggle('active', isActive);
            $button.setAttribute('aria-pressed', String(isActive));
        });

        $form.querySelectorAll<HTMLElement>('[data-language]').forEach(($row) => {
            $row.hidden = choice !== ALL && $row.dataset.language !== choice;
        });
    };

    [$all, ...$languages].forEach(($button) => $button.addEventListener('click', () => {
        choice = $button.dataset.translationLanguage ?? ALL;
        write(choice);
        apply();
    }));

    apply();
};
