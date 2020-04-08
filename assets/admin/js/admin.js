$(function () {
    /**
     * Extend jQuery to filter case insensitive.
     */
    $.expr[":"].contains = $.expr.createPseudo(function (arg) {
        return function (e) {
            return $(e).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
        };
    });

    /**
     * Use Bootbox for yii confirm dialogs.
     */
    yii.confirm = function (message, ok, cancel) {
        var $link = $(this);

        bootbox.confirm(message, function (result) {
            if (result) {
                if ($link.data('ajax')) {
                    _ajaxLink($link);
                } else {
                    !ok || ok();
                }
            } else {
                !cancel || cancel();
            }
        });
    };

    /**
     * Use same functionality as yii.confirm for regular data-ajax links.
     */
    $('[data-ajax]').click(function (e) {
        var $link = $(this);

        if (!$link.data('confirm')) {
            _ajaxLink($($link));
            e.preventDefault();
        }
    });

    /**
     * Helper function for yii.confirm and regular data-ajax links.
     * @param $link
     * @private
     */
    function _ajaxLink($link) {
        var $target = $($link.data('target')),
            action = $link.data('ajax');

        $.ajax({
            url: $link.attr('href'),
            method: $link.data('method') || 'post',
            params: $link.data('params'),
            success: function () {
                if ($target.length) {
                    if (action === 'remove') {
                        $target.remove();

                    } else if (action === 'success') {
                        $target.toggleClass('bg-success');

                    } else if (action === 'select') {
                        $target.toggleClass('is-selected');
                    }
                }
            }
        });
    }

    /**
     * Toggle form groups based on "data-form-toggle" attribute.
     *
     * The first array position  represent all possible values on which all target elements listed
     * in the second array position will be hidden. Elements can be either a class or id selector
     * or the name of the field in which case the corresponding row will be hidden.
     *
     * [
     *     [
     *         [3,6],
     *         ["section-content_de","section-content"]
     *     ],
     * ]
     */
    $('[data-form-toggle]').change(function () {
        var $input = $(this),
            $option = $input.find('option:selected');

        // Show all previously hidden elements.
        if ($input.data('targets')) {
            $($input.data('targets')).each(function () {
                this.show();
            });
        }

        $input.data('targets', []);

        $.each($input.data('form-toggle'), function (x, data) {
            var values = $.isArray(data[0]) ? data[0] : [data[0]],
                targets = $.isArray(data[1]) ? data[1] : [data[1]],
                matches,
                value,
                z;

            value = String($option.length ?
                ((matches = String(values[0]).match(/^data-([\w-]+)/)) ? $option.data(matches[1]) : $input.val()) :
                ($input.prop('checked') ? $input.val() : 0));

            for (x = 0; x < values.length; x++) {
                if (String(values[x]) === value) {
                    for (z = 0; z < targets.length; z++) {
                        $input.data('targets').push($(targets[z].match(/^[.#]/) ? targets[z] : ('.field-' + targets[z])).hide());
                    }

                    break;
                }
            }
        });

        Skeleton.toggleHr();

    }).filter(':visible').trigger('change');

    /**
     * Toggle form groups based on "data-form-toggle" tag.
     */
    $('[data-form-target]').change(function () {
        var $input = $(this),
            values = $input.find('option:selected').data('value'),
            targets = $input.data('form-target'),
            i;

        if (!$.isArray(values)) {
            values = [values];
        }

        if (!$.isArray(targets)) {
            targets = [targets];
        }

        for (i = 0; i < targets.length; i++) {
            $(targets[i].match(/^[.#]/) ? targets[i] : ("#" + targets[i])).html(values[i]);
        }

        Skeleton.toggleHr();

    }).filter(':visible').trigger('change');

    /**
     * Signup form.
     * @returns {jQuery}
     */
    $.fn.signupForm = function () {
        return $(this).on('beforeValidate', function () {
            var $token = $('#token');

            if (!$token.val()) {
                $('#tz').val(jstz.determine().name());
                $('#honeypot').val('');

                $.get($token.data('url'), function (data) {
                    $token.val(data);
                });
            }
        });
    };

    /**
     * Enables filter in ButtonDropdown.
     */
    $.fn.dropdownFilter = function () {
        var $dropdown = $(this),
            $filter = $dropdown.find('.dropdown-filter'),
            $items = $filter.parent().next().nextAll();

        $dropdown.on('shown.bs.dropdown', function () {
            $filter.focus();
        });

        $filter.keyup(function (e) {
            var val = $filter.val(),
                $target;

            $items.show();

            if (val !== '') {
                $items.not(':contains("' + val + '")').hide();

                if (e.which === 13) {
                    $target = $items.filter('a:visible').eq(0);

                    if ($target.length) {
                        window.location.href = $target.attr('href');
                    }
                }
            }
        });
    };

    $(document).ajaxError(function (e, data) {
        var error = data.responseText;

        if (error) {
            bootbox.alert(error);
        }
    });

    Skeleton.initContent();
});

var Skeleton = {
    /**
     * Init content.
     * @param container
     */
    initContent: function (container) {
        var _ = this,
            $container = $(container || 'body');

        // Bootstrap tooltips.
        $container.find('[data-toggle="tooltip"]').tooltip();

        // Timeago.
        if ($.hasOwnProperty('timeago')) {
            // noinspection JSUnresolvedFunction
            $container.find('.timeago').timeago();
        }

        if (_.hasJUI()) {
            // Sortable.
            $container.find('.sortable').each(function () {
                var $sortable = $(this);
                $sortable.sortable({
                    axis: 'y',
                    handle: '.sortable-handle',
                    helper: function (e, $target) {
                        var $children = $target.children(),
                            $clone = $target.clone();

                        $clone.children().each(function (index) {
                            $(this).width($children.eq(index).outerWidth());
                        });

                        return $clone;
                    },
                    update: function () {
                        $.post($sortable.data('sort-url'), $(this).sortable('serialize'));
                    }
                });
            });
        }
    },

    /**
     * Loads given url and replaces the selected element. Initializes
     * @param target
     * @param data
     */
    replaceWithAjax: function (target, data) {
        var _ = this,
            $target = $(target);

        data = $.extend({url: document.location.href}, (typeof data === 'string') ? {url: data} : (data || {}));

        $.ajax(data).done(function (html) {
            _.initContent($target.html($('<div>').html(html).find(target).html()));
        });
    },

    /**
     * Toggles <hr> based on visibility of neighbors.
     */
    toggleHr: function () {
        $('hr').each(function () {
            var $hr = $(this);
            // noinspection JSCheckFunctionSignatures
            $hr.toggle($hr.nextUntil('hr').filter(':visible').length > 0);
        });
    },

    /**
     * Whether jQuery UI is loaded.
     * @return {boolean}
     */
    hasJUI: function () {
        return $.hasOwnProperty('ui');
    }
};