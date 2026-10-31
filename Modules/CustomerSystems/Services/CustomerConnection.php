<?php

namespace Modules\CustomerSystems\Services;

class CustomerConnection
{
    public static function client(array $settings)
    {
        if (($settings['type'] ?? '') === 'shopware6') {
            return new Shopware6Client(new Shopware6Transport($settings));
        }
        return new ShopwareClient(new ShopwareTransport($settings));
    }
}
