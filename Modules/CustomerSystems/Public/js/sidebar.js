(function ($) {
    'use strict';
    function loadOrders(panel) {
        panel.find('.customer-systems-orders').each(function () {
            var target = $(this);
            $.ajax({ url: target.attr('data-url'), dataType: 'html', timeout: 25000, cache: false })
                .done(function (html) {
                    if (/<(?:html|form|script)[\s>]/i.test(html)) {
                        target.text('Unerwartete Antwort. Bitte FreeScout neu laden.');
                    } else { target.html(html); }
                })
                .fail(function (xhr) {
                    target.text(xhr.status === 403 ? 'Keine Berechtigung für dieses Ticket.' : 'Bestellungen konnten nicht geladen werden. Bitte aktualisieren.');
                });
        });
    }
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
                    loadOrders(panel);
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
        $('.customer-systems').each(function () {
            var panel = $(this), sidebar = panel.closest('#conv-layout-customer');
            // Preserve the native customer profile above all sidebar boxes.
            var firstBox = sidebar.find('.conv-sidebar-block').not(panel).first();
            if (firstBox.length) { panel.insertBefore(firstBox); }
            load(panel);
        });
    });
    $(document).on('click', '.customer-systems-retry', function () {
        load($(this).closest('.customer-systems'));
    });
})(jQuery);
