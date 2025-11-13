import htmx from "htmx.org";

htmx.onLoad(($node) => {
    if (!($node instanceof HTMLElement)) {
        return;
    }

    ($node.querySelectorAll('[data-check-all]') as NodeListOf<HTMLInputElement>).forEach($checkbox => {
        const $parent = ($checkbox.dataset.checkAll ? document.querySelector($checkbox.dataset.checkAll!) : null) as HTMLElement | null;

        $checkbox.onchange = () => {
            (($parent || document).querySelectorAll('[data-check="multiple"]') as NodeListOf<HTMLInputElement>).forEach(($el) => {
                $el.checked = $checkbox.checked;
            });
        };
    });

    ($node.querySelectorAll('[data-check="single"]') as NodeListOf<HTMLInputElement>).forEach(($checkbox) => {
        const $parent = $checkbox.closest('form') || document;

        $checkbox.onchange = () => {
            if ($checkbox.checked) {
                ($parent.querySelectorAll(`[data-check="single"][name="${$checkbox.name}"]:checked`) as NodeListOf<HTMLInputElement>).forEach($el => {
                    if ($el !== $checkbox) {
                        $el.checked = false;
                    }
                });
            }
        }
    })
})