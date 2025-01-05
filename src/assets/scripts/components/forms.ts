const toggleHr = (form: HTMLFormElement) => {
    form.querySelectorAll('hr').forEach(($hr: HTMLElement) => {
        let visible = false;
        let $el = $hr.previousElementSibling;

        while ($el && $el.tagName !== 'HR') {
            if ($el.checkVisibility()) {
                visible = true;
                break;
            }

            $el = $el.previousElementSibling;
        }

        if (visible) {
            $el = $hr.nextElementSibling;
            visible = false;

            while ($el && !$el.classList.contains('form-group-sticky')) {
                if ($el.classList.contains('form-group') && $el.checkVisibility()) {
                    visible = true;
                    break;
                }

                $el = $el.nextElementSibling;
            }
        }

        $hr.classList.toggle('d-none', !visible);
    });
}

export const toggleTargetsOnChange = ($selects: NodeListOf<HTMLSelectElement | HTMLInputElement>) => {
    $selects.forEach(($input) => {
        let allSelectors: string[][] = [];
        let allValues: string[][] = [];
        let $targets: Set<HTMLElement>;

        JSON.parse($input.dataset.formToggle).forEach((data: object) => {
            let [values, selectors] = Object.values(data);
            allValues.push(!Array.isArray(values) ? [String(values)] : values.map(String));

            allSelectors.push((!Array.isArray(selectors) ? [selectors] : selectors).map((selector: string) => {
                return selector.match(/^[.#]/) ? selector : `.field-${selector}`;
            }));
        });

        console.log(allSelectors);

        const onChange = () => {
            const selected = $input.type.toLowerCase() !== 'checkbox' || ($input as HTMLInputElement).checked
                ? String($input.value)
                : '0';

//         value = String($option.length ?
//             ((matches = String(values[0]).match(/^data-([\w-]+)/)) ? $option.data(matches[1]) : $input.val()) :
//             ($input.prop('checked') ? $input.val() : 0));

            if ($targets) {
                $targets.forEach(($el: HTMLElement) => $el.classList.remove('d-none'));
            }

            $targets = new Set();

            allValues.forEach((values: string[], key: number) => {
                values.forEach((value: string) => {
                    if (String(value) === selected) {
                        allSelectors[key].forEach((selector: string) => {
                            const $target = document.querySelector(selector) as HTMLElement;

                            if ($target) {
                                $targets.add($target);
                                $target.classList.add('d-none');
                            }
                        });
                    }
                });
            });

            toggleHr($input.form);
        }

        $input.addEventListener('change', onChange);

        if ($input.checkVisibility()) {
            onChange();
        }
    });
};


//
// /**
//  * Toggle form groups based on "data-form-toggle" tag.
//  */
// $('[data-form-target]').change(function () {
//     var $input = $(this),
//         values = $input.find('option:selected').data('value'),
//         targets = $input.data('form-target'),
//         i;
//
//     if (!$.isArray(values)) {
//         values = [values];
//     }
//
//     if (!$.isArray(targets)) {
//         targets = [targets];
//     }
//
//     for (i = 0; i < targets.length; i++) {
//         $(targets[i].match(/^[.#]/) ? targets[i] : ("#" + targets[i])).each(function () {
//             this[this.value !== undefined ? "value" : "innerHTML"] = values[i];
//         });
//     }
//
//     Skeleton.toggleHr();
//
// }).filter(':visible').trigger('change');
//
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