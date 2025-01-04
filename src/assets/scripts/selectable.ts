// noinspection JSUnusedGlobalSymbols
export default (grid: string) => {
    const $grid = document.querySelector(grid) as HTMLTableElement;
    const $checkboxes = $grid.querySelectorAll('[data-id="check"]') as NodeListOf<HTMLInputElement>;
    const $all = $grid.querySelector('[data-id="check-all"]') as HTMLInputElement | null;

    if ($all) {
        $all.onchange = () => $checkboxes.forEach(($el) => $el.checked = $all.checked);
    }

    $checkboxes.forEach(($checkbox) => $checkbox.onchange = () => {
        if (!$all) {
            if ($checkbox.checked) {
                $checkboxes.forEach(($el) => $el.checked = ($el === $checkbox));
            }
        } else {
            const $buttons = $grid.querySelectorAll('[data-id="check-button"]') as NodeListOf<HTMLButtonElement>;
            let display = 'none';

            $checkboxes.forEach(($el) => {
                if ($el.checked) {
                    display = 'block';
                }
            });

            $buttons.forEach(($button) => $button.style.display = display);
        }
    });
}