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
     * Bootstrap tooltips.
     */
    $('[data-toggle="tooltip"]').tooltip();

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

    if (window.hasOwnProperty('CKEDITOR')) {
        CKEDITOR.on('dialogDefinition', function (ev) {
            var dialogName = ev.data.name,
                dialogDefinition = ev.data.definition;

            if (dialogName === 'link') {
                var infoTab = dialogDefinition.getContents('info'),
                    url = infoTab.get('url'),
                    targetTab = dialogDefinition.getContents('target'),
                    targetType = targetTab.get('linkTargetType');

                infoTab.remove('protocol');
                infoTab.remove('emailOptions');
                infoTab.remove('telOptions');
                infoTab.remove('anchorOptions');
                infoTab.remove('browse');

                // Cannot remove linkType without breaking the functionality...
                infoTab.get('linkType').style = 'display: none';

                url.onKeyUp = function (data) {
                };

                url.setup = function (data) {
                    this.allowOnChange = false;
                    if (data.url) {
                        this.setValue((typeof data.url.protocol == 'string' ? data.url.protocol : '') + data.url.url);
                    }
                    this.allowOnChange = true;
                };
                url.commit = function (data) {
                    data.url = {protocol: '', url: this.getValue()};
                };

                // Remove useless targets.
                targetType.items = [targetType.items[0], targetType.items[3]];
            }
        });
    }
});