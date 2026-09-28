/**
 * A form's translated rows (`data-language`) either stand beneath each other — `inline` — or one language shows at a
 * time, picked from the toolbar's buttons — `tabs`. The server names the project's default
 * (`data-translation-layout`); the switch and the language are remembered per browser. Switching only hides rows,
 * so nothing typed is lost and every language still posts. A language holding a rejected field comes to the front.
 */
const LAYOUT_KEY = 'translationLayout';
const LANGUAGE_KEY = 'translationLanguage';

const read = (key: string): string | null => {
    try {
        return window.localStorage.getItem(key);
    } catch {
        return null;
    }
};

const write = (key: string, value: string): void => {
    try {
        window.localStorage.setItem(key, value);
    } catch {
        // A private window or blocked storage keeps the choice for this page only.
    }
};

export default ($form: HTMLFormElement): void => {
    const $toolbar = $form.querySelector<HTMLElement>('[data-translation-toolbar]');
    const $toggle = $toolbar?.querySelector<HTMLButtonElement>('[data-translation-toggle]');
    const $tabs = [...($toolbar?.querySelectorAll<HTMLButtonElement>('[data-translation-language]') ?? [])];

    if (!$toolbar || !$toggle || !$tabs.length) {
        return;
    }

    const languages = $tabs.map(($tab) => $tab.dataset.translationLanguage!);
    const $invalid = $form.querySelector<HTMLElement>('[data-language]:has([aria-invalid="true"])');

    let layout = read(LAYOUT_KEY) ?? $form.dataset.translationLayout ?? 'inline';
    let language = $invalid?.dataset.language ?? read(LANGUAGE_KEY) ?? languages[0];

    if (!languages.includes(language)) {
        language = languages[0];
    }

    const apply = () => {
        const isTabs = layout === 'tabs';

        $form.dataset.translationLayout = layout;
        $toggle.textContent = (isTabs ? $toggle.dataset.labelTabs : $toggle.dataset.labelInline) ?? '';

        $tabs.forEach(($tab) => {
            $tab.hidden = !isTabs;
            $tab.setAttribute('aria-pressed', String($tab.dataset.translationLanguage === language));
        });

        $form.querySelectorAll<HTMLElement>('[data-language]').forEach(($row) => {
            $row.hidden = isTabs && $row.dataset.language !== language;
        });
    };

    $toggle.addEventListener('click', () => {
        layout = layout === 'tabs' ? 'inline' : 'tabs';
        write(LAYOUT_KEY, layout);
        apply();
    });

    $tabs.forEach(($tab) => $tab.addEventListener('click', () => {
        language = $tab.dataset.translationLanguage!;
        write(LANGUAGE_KEY, language);
        apply();
    }));

    apply();
};
