export const toggleTargetsOnChange = ($selects: NodeListOf<HTMLSelectElement>) => {
    $selects.forEach(($select: HTMLSelectElement | HTMLInputElement) => {
        let allSelectors: string[][] = [];
        let allValues: string[][] = [];
        let $targets: Set<HTMLElement>;

        JSON.parse($select.dataset.formToggle).forEach((data: object) => {
            let [values, selectors] = Object.values(data);
            allValues.push(!Array.isArray(values) ? [String(values)] : values.map(String));

            allSelectors.push((!Array.isArray(selectors) ? [selectors] : selectors).map((selector: string) => {
                return selector.match(/^[.#]/) ? selector : `.field-${selector}`;
            }));
        });

        console.log(allSelectors);

        const onChange = () => {
            const selected = String($select.value);

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
        }

        $select.onchange = () => onChange();

        if ($select.checkVisibility()) {
            onChange();
        }
    });
};

// $('[data-form-toggle]').change(function () {
//     var $input = $(this),
//         $option = $input.find('option:selected'),
//         $targets = $input.data('targets');
//
//     if ($targets) {
//         $targets.show().find('[data-form-toggle]').trigger('change');
//     }
//
//     $targets = $();
//
//     $.each($input.data('form-toggle'), function (x, data) {
//         var values = $.isArray(data[0]) ? data[0] : [data[0]],
//             targets = $.isArray(data[1]) ? data[1] : [data[1]],
//             matches,
//             value,
//             z;
//
//         value = String($option.length ?
//             ((matches = String(values[0]).match(/^data-([\w-]+)/)) ? $option.data(matches[1]) : $input.val()) :
//             ($input.prop('checked') ? $input.val() : 0));
//
//         for (x = 0; x < values.length; x++) {
//             if (String(values[x]) === value) {
//                 for (z = 0; z < targets.length; z++) {
//                     $targets = $targets.add($(targets[z].match(/^[.#]/) ? targets[z] : ('.field-' + targets[z])).hide());
//                 }
//
//                 break;
//             }
//         }
//     });
//
//     $input.data('targets', $targets);
//     Skeleton.toggleHr();
//
// }).filter(':visible').trigger('change');
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