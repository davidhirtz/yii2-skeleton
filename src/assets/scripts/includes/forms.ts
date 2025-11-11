const getFieldSelectors = (selectors: string | string[]): Set<HTMLElement> => {
    const $elements = new Set<HTMLElement>();

    selectors = (!Array.isArray(selectors) ? [selectors] : selectors).map((selector: string) => {
        return selector.match(/^[.#]/) ? selector : `.field-${selector}`;
    })

    selectors.forEach((selector: string) => {
        (document.querySelectorAll(selector) as NodeListOf<HTMLElement>).forEach(($el) => $elements.add($el));
    });

    return $elements;
}

export const toggleTargetsOnChange = ($input: HTMLSelectElement | HTMLInputElement) => {
    let $allTargets: Set<HTMLElement>[] = [];
    let allValues: string[][] = [];
    let $currentTargets: Set<HTMLElement>;

    JSON.parse($input.dataset.formToggle!).forEach((data: object) => {
        let [values, selectors] = Object.values(data);
        allValues.push(!Array.isArray(values) ? [String(values)] : values.map(String));

        $allTargets.push(getFieldSelectors(selectors));
    });

    const onChange = () => {
        const selected = $input.type.toLowerCase() !== 'checkbox' || ($input as HTMLInputElement).checked
            ? String($input.value)
            : '0';

        if ($currentTargets) {
            $currentTargets.forEach(($el: HTMLElement) => $el.classList.remove('d-none'));
        }

        $currentTargets = new Set();

        allValues.forEach((values: string[], key: number) => {
            values.forEach((value: string) => {
                if (String(value) === selected) {
                    $allTargets[key].forEach($target => {
                        $currentTargets.add($target);
                        $target.classList.add('d-none');
                    });
                }
            });
        });

        const event = new Event('reflow');
        $input.form!.dispatchEvent(event);
    }

    $input.addEventListener('change', onChange);

    if ($input.checkVisibility()) {
        onChange();
    }
};

export const updateTargetsOnChange = ($select: HTMLSelectElement) => {
    const $targets = getFieldSelectors(JSON.parse($select.dataset.formTarget!));

    const onChange = () => {
        const values = JSON.parse($select.selectedOptions[0].dataset.value!);
        let key = 0;

        $targets.forEach(($target) => {
            if($target.tagName.toLowerCase() === 'input') {
                ($target as HTMLInputElement).value = values[key++]
            } else {
                $target.innerHTML = values[key++];
            }
        });
    };

    $select.addEventListener('change', onChange);

    if ($select.checkVisibility()) {
        onChange();
    }
};

// /**
//  * Enables filter in ButtonDropdown.
//  */
// $.fn.dropdownFilter = function () {
//     var $dropdown = $(this),
//         $filter = $dropdown.find('.dropdown-filter'),
//         $items = $filter.parent().next().nextAll();
//
//     $dropdown.on('shown.bs.dropdown', function () {
//         $filter.focus();
//     });
//
//     $filter.keyup(function (e) {
//         var val = $filter.val(),
//             $target;
//
//         $items.show();
//
//         if (val !== '') {
//             $items.not(':contains("' + val + '")').hide();
//
//             if (e.which === 13) {
//                 $target = $items.filter('a:visible').eq(0);
//
//                 if ($target.length) {
//                     window.location.href = $target.attr('href');
//                 }
//             }
//         }
//     });
// };