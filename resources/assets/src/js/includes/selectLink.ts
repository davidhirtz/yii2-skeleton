/**
 * Points the link sharing the select's `.form-action` at the URL the selected option carries in `data-url`, and
 * hides it while the selected option has none — the prompt.
 */
export default ($select: HTMLSelectElement) => {
    const $link = $select.closest('.form-action')?.querySelector<HTMLAnchorElement>('a');

    if ($link) {
        $select.addEventListener('change', () => {
            const url = $select.selectedOptions[0]?.dataset.url;

            if (url) {
                $link.href = url;
            }

            $link.hidden = !url;
        });
    }
}
