/**
 * A field whose hint holds a count (`data-character-counter`, the count in `[data-character-count]` inside the hint
 * the field names in `aria-describedby`) keeps it current while typing.
 */
export default ($input: HTMLInputElement | HTMLTextAreaElement): void => {
    const $count = ($input.getAttribute('aria-describedby') ?? '')
        .split(' ')
        .map((id) => document.getElementById(id)?.querySelector<HTMLElement>('[data-character-count]'))
        .find(($element) => $element);

    if (!$count) {
        return;
    }

    $input.addEventListener('input', () => {
        $count.textContent = String([...$input.value].length);
    });
};
