$(function () {
    $.fn.signupForm = function () {
        var $token = $('#token');

        if (!$token.val()) {
            $.get($token.data('url'), function (data) {
                $token.val(data);
            });
        }

        return $(this).on('beforeValidate', function () {
            $('#tz').val(jstz.determine().name());
            $('#honeypot').val('');
        });
    };
});