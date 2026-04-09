import htmx from "htmx.org";

htmx.onLoad((elt) => {
    const $container = elt as HTMLElement;

    $container.querySelectorAll<HTMLInputElement>('[data-check-all]')
        .forEach($checkbox => {
            const $parent: HTMLElement | null = $checkbox.dataset.checkAll
                ? document.querySelector($checkbox.dataset.checkAll!)
                : null;

            $checkbox.onchange = () => {
                ($parent || document).querySelectorAll<HTMLInputElement>('[data-check="multiple"]')
                    .forEach(($el) => $el.checked = $checkbox.checked);
            };
        });

    $container.querySelectorAll<HTMLInputElement>('[data-check="single"]')
        .forEach(($checkbox) => {
            const $parent = $checkbox.closest('form') || document;

            $checkbox.onchange = () => {
                if ($checkbox.checked) {
                    ($parent.querySelectorAll<HTMLInputElement>(`[data-check="single"][name="${$checkbox.name}"]:checked`))
                        .forEach($el => {
                            if ($el !== $checkbox) {
                                $el.checked = false;
                            }
                        });
                }
            }
        })
})