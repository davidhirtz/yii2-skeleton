import combobox from "./combobox";
import {openUnder} from "./popover";

const normalize = (text: string) => text.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();

// The select stays in the form, hidden, and remains what is posted: the picked options become tags in front of a
// search input whose listbox offers the others. A selected option that is disabled, or locked to stay posted, is a
// tag nobody can remove.
export default ($select: HTMLSelectElement) => {
    if ($select.dataset.multiselectReady !== undefined) {
        return;
    }

    $select.dataset.multiselectReady = '';

    const removeLabel = $select.dataset.multiselect || '{label}';
    const listboxId = `${$select.id}-listbox`;

    const $control = document.createElement('div');
    $control.className = 'input multiselect';

    const $input = document.createElement('input');
    $input.type = 'text';
    $input.className = 'multiselect-search';
    $input.autocomplete = 'off';
    $input.disabled = $select.disabled;
    $input.setAttribute('role', 'combobox');
    $input.setAttribute('aria-autocomplete', 'list');
    $input.setAttribute('aria-controls', listboxId);
    $input.setAttribute('aria-expanded', 'false');

    const $popover = document.createElement('div');
    $popover.className = 'dropdown-menu multiselect-results';
    $popover.popover = 'manual';

    const $listbox = document.createElement('ul');
    $listbox.className = 'dropdown-list';
    $listbox.id = listboxId;
    $listbox.setAttribute('role', 'listbox');

    $control.append($input);
    $popover.append($listbox);
    $select.hidden = true;
    $select.after($control, $popover);

    const $label = $select.id ? document.querySelector<HTMLLabelElement>(`label[for="${CSS.escape($select.id)}"]`) : null;

    if ($label) {
        $label.id ||= `${$select.id}-label`;
        $input.setAttribute('aria-labelledby', $label.id);

        // The label names the hidden select, which cannot take the focus.
        $label.addEventListener('click', (event: Event) => {
            event.preventDefault();
            $input.focus();
        });
    }

    const state = combobox($input, $listbox, '[role="option"]');
    const options = () => [...$select.options];
    const isFixed = ($option: HTMLOptionElement) => $option.disabled || $option.dataset.locked !== undefined;

    const renderTags = () => {
        $control.querySelectorAll('.multiselect-tag').forEach(($tag) => $tag.remove());

        options().filter(($option) => $option.selected).forEach(($option) => {
            const $tag = document.createElement('span');
            $tag.className = 'badge multiselect-tag';
            $tag.textContent = $option.text;

            if (!isFixed($option) && !$select.disabled) {
                const $remove = document.createElement('button');
                $remove.type = 'button';
                $remove.className = 'multiselect-remove';
                $remove.dataset.value = $option.value;
                $remove.setAttribute('aria-label', removeLabel.replace('{label}', $option.text));

                const $icon = document.createElement('span');
                $icon.className = 'fas fa-xmark';
                $icon.setAttribute('aria-hidden', 'true');
                $remove.append($icon);
                $tag.append($remove);
            }

            $input.before($tag);
        });
    };

    const renderOptions = () => {
        const query = normalize($input.value.trim());
        $listbox.replaceChildren();

        options()
            .filter(($option) => !$option.selected && !$option.disabled && normalize($option.text).includes(query))
            .forEach(($option) => {
                const $button = document.createElement('button');
                $button.type = 'button';
                $button.className = 'dropdown-option';
                $button.tabIndex = -1;
                $button.dataset.value = $option.value;
                $button.setAttribute('role', 'option');
                $button.textContent = $option.text;

                const $item = document.createElement('li');
                $item.className = 'dropdown-item';
                $item.setAttribute('role', 'none');
                $item.append($button);

                $listbox.append($item);
            });

        state.refresh();
    };

    let teardown: (() => void) | null = null;

    // A manual popover gets no light dismiss, and the listener has to go again with it: the field lives inside the
    // swapped region, so a listener left on the document would outlive every navigation.
    const pointerdown = (event: Event) => {
        if (!$control.contains(event.target as Node) && !$popover.contains(event.target as Node)) {
            close();
        }
    };

    const open = () => {
        renderOptions();

        if (!$listbox.childElementCount) {
            close();
            return;
        }

        document.addEventListener('pointerdown', pointerdown);
        teardown = openUnder($control, $popover, () => document.removeEventListener('pointerdown', pointerdown))
            ?? teardown;

        state.setExpanded(true);
    };

    const close = () => {
        if ($popover.matches(':popover-open')) {
            $popover.hidePopover();
        }

        teardown?.();
        teardown = null;
        state.setExpanded(false);
    };

    const setSelected = (value: string, selected: boolean) => {
        const $option = options().find(($option) => $option.value === value);

        if (!$option || isFixed($option)) {
            return;
        }

        $option.selected = selected;
        $select.dispatchEvent(new Event('change', {bubbles: true}));

        $input.value = '';
        renderTags();
        $popover.matches(':popover-open') ? open() : renderOptions();
        $input.focus();
    };

    $control.addEventListener('click', (event: Event) => {
        const $remove = (event.target as HTMLElement).closest<HTMLElement>('.multiselect-remove');

        if ($remove) {
            setSelected($remove.dataset.value!, false);
            return;
        }

        if (!$input.disabled) {
            $input.focus();
            open();
        }
    });

    $listbox.addEventListener('click', (event: Event) => {
        const $option = (event.target as HTMLElement).closest<HTMLElement>('[role="option"]');

        if ($option) {
            event.preventDefault();
            setSelected($option.dataset.value!, true);
        }
    });

    // The focus stays in the input, so a press on an option must not take it there.
    $popover.addEventListener('mousedown', (event: Event) => event.preventDefault());

    $input.addEventListener('input', open);
    $input.addEventListener('blur', close);

    $input.addEventListener('keydown', (event: KeyboardEvent) => {
        const isOpen = $popover.matches(':popover-open');

        if (event.key === 'Escape') {
            if (isOpen) {
                event.preventDefault();
                close();
            }

            return;
        }

        if (event.key === 'Enter') {
            // Implicit submission would otherwise save the form while the options are still open.
            if (isOpen) {
                event.preventDefault();
                const $active = state.getActive();

                if ($active) {
                    setSelected($active.dataset.value!, true);
                }
            }

            return;
        }

        if (event.key === 'Backspace' && $input.value === '') {
            const $remove = [...$control.querySelectorAll<HTMLElement>('.multiselect-remove')].pop();

            if ($remove) {
                event.preventDefault();
                setSelected($remove.dataset.value!, false);
            }

            return;
        }

        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
            return;
        }

        event.preventDefault();

        if (!isOpen) {
            open();
        }

        state.move(event.key === 'ArrowDown' ? 1 : -1);
    });

    renderTags();
}
