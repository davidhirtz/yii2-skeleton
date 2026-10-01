/**
 * The WAI-ARIA combobox state of an input whose suggestions an endpoint swaps into a listbox: the focus stays in the
 * input and `aria-activedescendant` names the option the arrow keys reached. The ids are assigned here, after each
 * swap, since an id the endpoint generated would be counted per request and could repeat one on the page.
 */
export interface Combobox {
    getActive(): HTMLElement | null;

    move(step: 1 | -1): void;

    refresh(): void;

    setExpanded(expanded: boolean): void;
}

export default ($input: HTMLInputElement, $listbox: HTMLElement, selector: string): Combobox => {
    let $active: HTMLElement | null = null;

    const options = () => [...$listbox.querySelectorAll(selector)] as HTMLElement[];

    const activate = ($option: HTMLElement | null) => {
        $active?.setAttribute('aria-selected', 'false');
        $active = $option;

        if (!$option) {
            $input.removeAttribute('aria-activedescendant');
            return;
        }

        $option.setAttribute('aria-selected', 'true');
        $input.setAttribute('aria-activedescendant', $option.id);
        $option.scrollIntoView?.({block: 'nearest'});
    };

    return {
        getActive: () => $active?.isConnected ? $active : null,

        move(step) {
            const $options = options();

            if (!$options.length) {
                return;
            }

            const next = ($active ? $options.indexOf($active) : -1) + step;
            activate(next < 0 ? null : $options[Math.min(next, $options.length - 1)]);
        },

        refresh() {
            activate(null);

            options().forEach(($option, index) => {
                $option.id = `${$listbox.id}-option-${index}`;
                $option.setAttribute('aria-selected', 'false');
            });
        },

        setExpanded(expanded) {
            $input.setAttribute('aria-expanded', expanded ? 'true' : 'false');

            if (!expanded) {
                activate(null);
            }
        },
    };
};
