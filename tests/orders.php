<?php

require __DIR__.'/connections.php';
require __DIR__.'/../Modules/CustomerSystems/Services/OrderPresentation.php';

use Modules\CustomerSystems\Services\Shopware6Client;
use Modules\CustomerSystems\Services\BackendLink;
use Modules\CustomerSystems\Services\ApiException;
use Modules\CustomerSystems\Services\OrderPresentation;

$ownerId = '0123456789abcdef0123456789abcdef';
$orderRows = [];
foreach ([1, 4, 2, 7, 3, 6, 5] as $day) {
    $orderRows[] = [
        'id' => str_pad(dechex($day), 32, '0', STR_PAD_LEFT), 'orderNumber' => 'ORDER-'.$day,
        'orderDateTime' => '2026-11-0'.$day.'T13:00:00.000+00:00',
        'orderCustomer' => ['customerId' => $ownerId, 'customer' => ['id' => $ownerId, 'email' => 'test@example.com']],
        'stateMachineState' => ['technicalName' => 'open', 'translated' => ['name' => 'Offen']],
        'customFields' => ['private' => 'do-not-expose'], 'amountTotal' => 100,
    ];
}
$client = new Shopware6Client(function ($path, $body) use ($ownerId, $orderRows) {
    check($path === 'search/order' && $body['limit'] === 5, 'Order endpoint or limit wrong');
    check($body['sort'][0] === ['field' => 'orderDateTime', 'order' => 'DESC'], 'Orders must be newest first');
    check($body['filter'] === [
        ['type' => 'equals', 'field' => 'orderCustomer.customerId', 'value' => $ownerId],
        ['type' => 'equals', 'field' => 'orderCustomer.customer.email', 'value' => 'test@example.com'],
    ], 'Order lookup must bind both customer ID and authorized ticket email');
    check(isset($body['associations']['stateMachineState'], $body['associations']['orderCustomer']['associations']['customer']), 'Missing order associations');
    return ['data' => $orderRows];
});
$orders = $client->latestOrders($ownerId, 'test@example.com');
check(count($orders) === 5, 'Order result must be limited to five');
check(array_column($orders, 'number') === ['ORDER-7', 'ORDER-6', 'ORDER-5', 'ORDER-4', 'ORDER-3'], 'Order sorting incorrect');
check($orders[0]['status'] === 'Offen' && $orders[0]['date'] === '07.11.2026', 'Status or date normalization failed');
check(array_keys($orders[0]) === ['id', 'number', 'date', 'status', 'status_key'], 'Order payload allowlist failed');
check($orders[0]['status_key'] === 'open', 'Stable status key missing');
check(strpos(json_encode($orders), 'do-not-expose') === false, 'Order response leaked unneeded fields');
$row = $orderRows[0];
foreach ([
    array_replace_recursive($row, ['orderCustomer' => ['customerId' => 'ffffffffffffffffffffffffffffffff']]),
    array_replace_recursive($row, ['orderCustomer' => ['customer' => ['email' => 'someone-else@example.com']]]),
    array_replace_recursive($row, ['orderCustomer' => ['customer' => ['id' => 'ffffffffffffffffffffffffffffffff']]]),
    array_merge($row, ['id' => 'invalid']),
    array_merge($row, ['orderDateTime' => 'not-a-date']),
    'not-an-order',
] as $bad) {
    failsWith(function () use ($bad, $ownerId) {
        (new Shopware6Client(function () use ($bad) { return ['data' => [$bad]]; }))->latestOrders($ownerId, 'test@example.com');
    }, 'invalid_response');
}
$emptyClient = new Shopware6Client(function () { return ['data' => []]; });
check($emptyClient->latestOrders($ownerId, 'test@example.com') === [], 'No orders should return empty list');
failsWith(function () use ($ownerId) {
    (new Shopware6Client(function () { throw new ApiException('permission_denied', 403, 0, 'orders'); }))->latestOrders($ownerId, 'test@example.com');
}, 'permission_denied');
$base = 'https://shop.example.com/sub/admin';
check(BackendLink::order($base, $orders[0]['id']) === $base.'#/sw/order/detail/'.$orders[0]['id'].'/general', 'Order admin link wrong');
check(BackendLink::order($base, '123') === null, 'Invalid order ID accepted');
check(BackendLink::order('javascript:alert(1)', $orders[0]['id']) === null, 'Unsafe order link accepted');
check(OrderPresentation::status(['status_key' => 'completed', 'status' => 'Completed'])['class'] === 'label-success', 'Translated completion must retain colour');
check(OrderPresentation::status(['status_key' => 'in_progress', 'status' => 'In Bearbeitung'])['label'] === 'In Arb.', 'Long standard status must be abbreviated');
check(OrderPresentation::status(['status_key' => 'cancelled', 'status' => 'Storniert'])['class'] === 'label-danger', 'Cancelled order colour wrong');
check(OrderPresentation::status(['status_key' => 'custom', 'status' => 'Abgeschlossen'])['class'] === 'label-default', 'Custom states must not inherit a misleading colour by name');
check(OrderPresentation::compact('ÄÖÜ1234567890', 10) === 'ÄÖÜ123456…', 'Unicode order numbers must truncate safely');
check(OrderPresentation::compact('413280', 10) === '413280', 'Short order numbers must stay intact');
echo 'OK: '.$passed." checks including order ownership, ordering and links passed\n";
