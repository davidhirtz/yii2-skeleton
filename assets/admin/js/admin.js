$(function () {
    /**
     * Use Bootbox for yii confirm dialogs.
     */
    yii.confirm = function (message, ok, cancel) {
        var $link = $(this);

        bootbox.confirm(message, function (result) {
            if (result) {
                if ($link.data('ajax')) {

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
                                }

                            }
                        }
                    });
                } else {
                    !ok || ok();
                }
            } else {
                !cancel || cancel();
            }
        });
    };

    /**
     * Toggle form groups based on "data-form-toggle" tag.
     */
    $('[data-form-toggle]').change(function () {
        var $input = $(this),
            $option = $input.find('option:selected');

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
    })
        .filter(':visible').trigger('change');

    /**
     * Toggle form groups based on "data-form-toggle" tag.
     */
    $('[data-form-target]').change(function () {
        var $input = $(this),
            value = $input.find('option:selected').data('value'),
            target = $input.data('form-target');

        $(target.match(/^[.#]/) ? target : ("#" + target)).html(value);
    })
        .filter(':visible').trigger('change');

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
            $container.find('.timeago').timeago();
        }

        if (_.hasJUI()) {
            // Sortable.
            $container.find('.sortable').each(function () {
                var $sortable = $(this);
                $sortable.sortable({
                    clientOptions: {
                        handle: '.sortable-handle',
                        axis: 'y'
                    },
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
     * Whether jQuery UI is loaded.
     * @return {boolean}
     */
    hasJUI: function () {
        return $.hasOwnProperty('ui');
    }
};