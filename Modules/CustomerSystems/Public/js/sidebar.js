(function ($) {
    'use strict';
    function load(panel) {
        var result = panel.find('.customer-systems-results');
        var button = panel.find('.customer-systems-retry');
        if (panel.data('loading')) { return; }
        panel.data('loading', true);
        button.prop('disabled', true);
        result.text('Kundendaten werden geladen …');
        $.ajax({ url: panel.attr('data-url'), dataType: 'html', timeout: 60000, cache: false })
            .done(function (html) {
                // Login redirects and error pages must not replace the sidebar.
                if (/<(?:html|form|script)[\s>]/i.test(html)) {
                    result.text('Unerwartete Antwort. Bitte FreeScout neu laden.');
                } else {
                    result.html(html);
                }
            })
            .fail(function (xhr) {
                result.text(xhr.status === 403 ? 'Keine Berechtigung für dieses Ticket.' :
                    'Kundendaten konnten nicht geladen werden. Bitte erneut versuchen.');
            })
            .always(function () {
                panel.data('loading', false);
                button.prop('disabled', false);
            });
    }
    $(function () {
        $('.customer-systems').each(function () { load($(this)); });
    });
    $(document).on('click', '.customer-systems-retry', function () {
        load($(this).closest('.customer-systems'));
    });
})(jQuery);
