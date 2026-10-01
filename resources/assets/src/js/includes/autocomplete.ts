import combobox from "./combobox";
import {openUnder} from "./popover";

// The options are rendered by the endpoint and swapped in by htmx, so this only opens the popover, moves the active
// option through it and writes the picked value back into the input.
export default ($container: HTMLElement) => {
    const $input = $container.querySelector('input') as HTMLInputElement | null;
    const $results = $container.querySelector('[data-autocomplete-results]') as HTMLElement | null;

    if (!$input || !$results) {
        return;
    }

    let teardown: (() => void) | null = null;

    const state = combobox($input, $results, '[data-autocomplete-value]');

    // A manual popover gets no light dismiss, and the listener has to go again with it: the field lives inside the
    // swapped region, so a listener left on the document would outlive every navigation.
    const pointerdown = (event: Event) => {
        if (!$container.contains(event.target as Node)) {
            close();
        }
    };

    const open = () => {
        document.addEventListener('pointerdown', pointerdown);
        teardown = openUnder($input, $results, () => document.removeEventListener('pointerdown', pointerdown))
            ?? teardown;

        state.setExpanded(true);
    };

    const close = () => {
        if ($results.matches(':popover-open')) {
            $results.hidePopover();
        }

        teardown?.();
        teardown = null;
        state.setExpanded(false);
    };

    const select = ($option: HTMLElement) => {
        $input.value = $option.dataset.autocompleteValue!;
        $input.dispatchEvent(new Event('change', {bubbles: true}));

        $results.innerHTML = '';
        close();
        $input.focus();
    };

    // The input's name carries the model and the attribute, and `hx-vals` cannot reach the element it sits on —
    // htmx evaluates a `js:` value in global scope, with neither `this` nor the triggering event.
    $input.addEventListener('htmx:config:request', (event: Event) => {
        const {body} = (event as CustomEvent).detail.ctx.request;

        [...body.keys()].forEach((key: string) => body.delete(key));
        body.set('q', $input.value);
    });

    $results.addEventListener('click', (event: Event) => {
        const $option = (event.target as HTMLElement).closest('[data-autocomplete-value]') as HTMLElement | null;

        if ($option) {
            event.preventDefault();
            select($option);
        }
    });

    // The focus stays in the input, so a press on an option must not take it there.
    $results.addEventListener('mousedown', (event: Event) => event.preventDefault());

    $input.addEventListener('keydown', (event: KeyboardEvent) => {
        const isOpen = $results.matches(':popover-open');

        if (event.key === 'Escape') {
            if (isOpen) {
                event.preventDefault();
                close();
            }

            return;
        }

        if (event.key === 'Enter') {
            if (!isOpen) {
                return;
            }

            // Implicit submission would otherwise save the form while the suggestions are still open.
            event.preventDefault();

            const $active = state.getActive();

            if ($active) {
                select($active);
            }

            return;
        }

        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
            return;
        }

        if (!$results.childElementCount) {
            return;
        }

        event.preventDefault();

        if (!isOpen) {
            open();
        }

        state.move(event.key === 'ArrowDown' ? 1 : -1);
    });

    // htmx fires the swap events on the element that issued the request, not on the one it swapped, so this
    // listens on the container the input sits in rather than on the results it fills.
    $container.addEventListener('htmx:after:swap', () => {
        state.refresh();
        $results.childElementCount ? open() : close();
    });
}
