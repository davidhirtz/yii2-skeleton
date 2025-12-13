const getElementSet = (selectors: string | string[]): Set<HTMLElement> => {
    const $elements = new Set<HTMLElement>();

    selectors = (!Array.isArray(selectors) ? [selectors] : selectors).map((selector: string) => {
        return selector.match(/^[.#]/) ? selector : `[data-id="${selector}"]`;
    })

    selectors.forEach((selector: string) => {
        (document.querySelectorAll<HTMLElement>(selector)).forEach(($el) => $elements.add($el));
    });

    return $elements;
}

const sanitizeSelectors = (selectors: string | string[]): string =>
    (Array.isArray(selectors) ? selectors : [selectors])
        .map(selector => selector.match(/^[.#]/) ? selector : `[data-id="${selector}"]`)
        .join(',')

export const toggle = ($input: HTMLSelectElement | HTMLInputElement) => {
    let $targets: Map<string, NodeListOf<HTMLElement>> = new Map();
    let $currentTargets: Set<HTMLElement>;

    for (const [key, selectors] of Object.entries(JSON.parse($input.dataset.toggle!))) {
        $targets.set(String(key), document.querySelectorAll(sanitizeSelectors(selectors as string[])));
    }

    const onChange = () => {
        const selected = $input.type.toLowerCase() !== 'checkbox' || ($input as HTMLInputElement).checked
            ? String($input.value)
            : '0';

        if ($currentTargets) {
            $currentTargets.forEach(($el: HTMLElement) => $el.hidden = false);
        }

        $currentTargets = new Set<HTMLElement>($targets.get(selected) || []);
        $currentTargets.forEach(($el: HTMLElement) => $el.hidden = true);
    }

    $input.addEventListener('change', onChange);

    if ($input.checkVisibility()) {
        onChange();
    }
};

export const updateTargetsOnChange = ($select: HTMLSelectElement) => {
    const $targets = getElementSet(JSON.parse($select.dataset.formTarget!));

    const onChange = () => {
        const values = JSON.parse($select.selectedOptions[0].dataset.value!);
        let key = 0;

        $targets.forEach(($target) => {
            if ($target.tagName.toLowerCase() === 'input') {
                ($target as HTMLInputElement).value = values[key++]
            } else {
                $target.innerHTML = values[key++];
            }
        });
    };

    $select.addEventListener('change', onChange);

    if ($select.checkVisibility()) {
        onChange();
    }
};