import {openUnder} from "./popover";

// The options are rendered by the endpoint and swapped in by htmx, so this only opens the popover, moves the focus
// through it and writes the picked value back into the input.
export default ($container: HTMLElement) => {
    const $input = $container.querySelector('input') as HTMLInputElement | null;
    const $results = $container.querySelector('[data-autocomplete-results]') as HTMLElement | null;

    if (!$input || !$results) {
        return;
    }

    let teardown: (() => void) | null = null;

    const options = () => [...$results.querySelectorAll('[data-autocomplete-value]')] as HTMLElement[];

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
    };

    const close = () => {
        if ($results.matches(':popover-open')) {
            $results.hidePopover();
        }

        teardown?.();
        teardown = null;
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

    $container.addEventListener('keydown', (event: KeyboardEvent) => {
        if (event.key === 'Escape') {
            close();
            return;
        }

        const $options = options();

        if (event.key === 'Enter') {
            if (!$results.matches(':popover-open')) {
                return;
            }

            // Implicit submission would otherwise save the form while the suggestions are still open.
            event.preventDefault();

            const $focused = document.activeElement as HTMLElement;

            if ($options.includes($focused)) {
                select($focused);
            }

            return;
        }

        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
            return;
        }

        if (!$options.length) {
            return;
        }

        event.preventDefault();

        const index = $options.indexOf(document.activeElement as HTMLElement);
        const next = index + (event.key === 'ArrowDown' ? 1 : -1);

        next < 0 ? $input.focus() : $options[Math.min(next, $options.length - 1)].focus();
    });

    // htmx fires the swap events on the element that issued the request, not on the one it swapped, so this
    // listens on the container the input sits in rather than on the results it fills.
    $container.addEventListener('htmx:after:swap', () => {
        $results.childElementCount ? open() : close();
    });
}
