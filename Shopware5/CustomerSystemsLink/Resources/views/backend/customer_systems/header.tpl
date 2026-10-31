{extends file="parent:backend/index/header.tpl"}

{block name="backend/base/header/javascript" append}
<script>
Ext.onReady(function () {
    var match = window.location.search.match(/[?&]customerSystemsCustomerId=([1-9][0-9]*)(?:&|$)/);
    if (!match) { return; }
    var opened = false;
    Shopware.app.Application.on('baseComponentsReady', function () {
        if (opened) { return; }
        opened = true;
        Shopware.app.Application.addSubApplication({
            name: 'Shopware.apps.Customer',
            action: 'detail',
            params: { customerId: parseInt(match[1], 10) }
        });
    });
});
</script>
{/block}
