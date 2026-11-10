interface Selection {
    value: string;
    text: string;
    description: string;
    url?: string;
}

// The input stays in the form while a picked record shows by name, so it keeps posting the value; clearing it shows the
// input again, empty, for a URL or another search.
export default ($container: HTMLElement) => {
    const $selected = $container.querySelector<HTMLElement>('.link-field-selected');
    const $wrap = $container.querySelector<HTMLElement>('.link-field-input');
    const $input = $wrap?.querySelector<HTMLInputElement>('input[name]');
    const $name = $container.querySelector<HTMLAnchorElement>('[data-link-field-name]');
    const $text = $container.querySelector<HTMLElement>('[data-link-field-text]');
    const $description = $container.querySelector<HTMLElement>('[data-link-field-description]');
    const $clear = $container.querySelector<HTMLButtonElement>('[data-link-field-clear]');

    if (!$selected || !$wrap || !$input) {
        return;
    }

    const show = (isSelected: boolean) => {
        $selected.hidden = !isSelected;
        $wrap.hidden = isSelected;
    };

    $container.addEventListener('autocomplete:select', (event: Event) => {
        const {text, description, url} = (event as CustomEvent<Selection>).detail;

        if ($text) {
            $text.textContent = text;
        }

        if ($description) {
            $description.textContent = description;
        }

        if ($name) {
            url ? $name.href = url : $name.removeAttribute('href');
        }

        show(true);
        $clear?.focus();
    });

    $clear?.addEventListener('click', () => {
        $input.value = '';
        show(false);
        $input.focus();
        $input.dispatchEvent(new Event('change', {bubbles: true}));
    });
}
