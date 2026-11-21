/**
 * A form's languages as tabs: "All fields" shows every translated row (`data-language`) beneath the other, a
 * language button only that language's. The server names the project's default (`data-translation-layout`:
 * `inline` for every field, `tabs` for a language); the choice is remembered per browser, but a language tab always
 * opens on the admin's content language (`data-content-language`), which clicking another tab leaves alone. With more
 * than two languages (`data-translation-multiple`) a click toggles a language, so the source and a translation can
 * show together; such a selection is remembered as it is, as far as the next form offers it. Switching only
 * hides rows, so nothing typed is lost and every language still posts. A rejected field in a language that does not
 * show brings every field back. The tabs sit inside the form or, placed by `FormContainer`, in a container of their own before it.
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

/**
 * Tabs swapped in after their form (a failed save renews them out of band) initialise from their side.
 */
export const initTranslationTabs = ($toolbar: HTMLElement): void => {
    const $form = document.getElementById($toolbar.dataset.translationToolbar ?? '');

    if ($form instanceof HTMLFormElement) {
        translationLayout($form);
    }
};

const translationLayout = ($form: HTMLFormElement): void => {
    // Inside the form, or in a container of its own beside it, naming the form.
    const $toolbar = $form.querySelector<HTMLElement>('[data-translation-toolbar]')
        ?? document.querySelector<HTMLElement>(`[data-translation-toolbar="${CSS.escape($form.id)}"]`);
    const $all = $toolbar?.querySelector<HTMLButtonElement>('[data-translation-all]');
    const $languages = [...($toolbar?.querySelectorAll<HTMLButtonElement>('[data-translation-language]') ?? [])];

    // Bound once: a page load reaches the tabs from their form and on their own.
    if (!$toolbar || !$all || !$languages.length || $toolbar.dataset.translationBound !== undefined) {
        return;
    }

    $toolbar.dataset.translationBound = '';

    const codes = $languages.map(($button) => $button.dataset.translationLanguage!);
    const isMultiple = $toolbar.dataset.translationMultiple !== undefined;
    const content = $toolbar.dataset.contentLanguage;
    const first = content && codes.includes(content) ? content : codes[0];
    const fallback = $form.dataset.translationLayout === 'tabs' ? first : ALL;
    const invalid = [...$form.querySelectorAll<HTMLElement>('[data-language]:has([aria-invalid="true"])')]
        .map(($row) => $row.dataset.language!);

    // Empty while every field shows; kept in the order of the tabs.
    let selection: string[] = [];

    const select = (languages: string[]): void => {
        selection = codes.filter((code) => languages.includes(code));

        if (isMultiple && selection.length === codes.length) {
            selection = [];
        }
    };

    const stored = read() ?? fallback;

    if (stored !== ALL) {
        const remembered = stored.split(',').filter((code) => codes.includes(code));
        select(isMultiple && remembered.length > 1 ? remembered : [first]);
    }

    // A rejected field in a language that does not show would go unseen: every field shows until the next choice.
    if (selection.length && invalid.some((language) => !selection.includes(language))) {
        selection = [];
    }

    const apply = () => {
        // Read by the styles, which mark the labels of the translated fields while not every language shows.
        $form.dataset.translationChoice = selection.length ? selection.join(' ') : ALL;

        [$all, ...$languages].forEach(($button) => {
            const language = $button.dataset.translationLanguage;
            const isActive = language ? selection.includes(language) : !selection.length;
            $button.classList.toggle('active', isActive);
            $button.setAttribute('aria-pressed', String(isActive));
        });

        $form.querySelectorAll<HTMLElement>('[data-language]').forEach(($row) => {
            $row.hidden = selection.length > 0 && !selection.includes($row.dataset.language!);
        });
    };

    // The browser's own check (`required`) cannot point at a field it cannot focus, and blocks the submit without a
    // word. `invalid` fires before it looks for one, so a field hidden in another language brings every field back.
    $form.addEventListener('invalid', (event) => {
        if ((event.target as HTMLElement).closest<HTMLElement>('[data-language]')?.hidden) {
            selection = [];
            apply();
        }
    }, true);

    [$all, ...$languages].forEach(($button) => $button.addEventListener('click', () => {
        const language = $button.dataset.translationLanguage;

        if (!language) {
            selection = [];
        } else if (!isMultiple || !selection.length) {
            select([language]);
        } else {
            // Leaving out the last language shows every field again.
            select(selection.includes(language) ? selection.filter((code) => code !== language) : [...selection, language]);
        }

        write(selection.length ? selection.join(',') : ALL);
        apply();
    }));

    apply();
};

export default translationLayout;
