<?php

namespace Modules\CustomerSystems\Services;

class CustomerConnection
{
    public static function client(array $settings)
    {
        return new Shopware6Client(new Shopware6Transport($settings));
    }
}
