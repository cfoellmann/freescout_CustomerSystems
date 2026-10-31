(function ($) {
    'use strict';
    $(function () {
        $('.customer-systems-settings').each(function () {
            var form = $(this);
            function update() {
                var type = form.find('[name="type"]').val();
                form.find('[data-sw]').each(function () {
                    $(this).toggle($(this).attr('data-sw') === type);
                });
            }
            form.find('[name="type"]').on('change', update);
            update();
            form.on('input change', 'input, select', function () {
                form.find('.customer-systems-test-result').empty().removeClass('alert alert-success alert-danger');
            });
            form.find('.customer-systems-test').on('click', function () {
                var button = $(this), result = form.find('.customer-systems-test-result');
                button.prop('disabled', true);
                result.removeClass('alert alert-success alert-danger').text('Verbindung wird geprüft …');
                $.ajax({ url: form.attr('data-test-url'), type: 'POST', data: form.serialize(), dataType: 'json', timeout: 25000 })
                    .done(function (data) {
                        result.addClass('alert ' + (data.ok ? 'alert-success' : 'alert-danger')).text(data.message);
                    })
                    .fail(function (xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message;
                        if (xhr.status === 403) { message = 'Nur Administratoren dürfen die Verbindung prüfen.'; }
                        if (xhr.status === 429) { message = 'Zu viele Tests. Bitte eine Minute warten.'; }
                        result.addClass('alert alert-danger').text(message || 'Test fehlgeschlagen. Bitte Sitzung und Netzwerk prüfen.');
                    })
                    .always(function () { button.prop('disabled', false); });
            });
        });
    });
})(jQuery);
