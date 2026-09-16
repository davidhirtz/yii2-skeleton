// `data-form-target` is a JSON list of plain CSS selectors. It used to accept a bare name too, resolved against
// `[data-id="…"]`, which is gone with the type toggling that needed it.
const getElementSet = (selectors: string[]): Set<HTMLElement> => {
    const $elements = new Set<HTMLElement>();

    selectors.forEach((selector: string) => {
        document.querySelectorAll<HTMLElement>(selector).forEach(($el) => $elements.add($el));
    });

    return $elements;
}

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
