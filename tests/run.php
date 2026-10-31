<?php

require __DIR__.'/../Modules/CustomerSystems/Services/ShopwareClient.php';
require __DIR__.'/../Modules/CustomerSystems/Services/BackendLink.php';
require __DIR__.'/../Modules/CustomerSystems/Services/ShopwareTransport.php';

use Modules\CustomerSystems\Services\ShopwareClient;
use Modules\CustomerSystems\Services\BackendLink;
use Modules\CustomerSystems\Services\ShopwareTransport;

$passed = 0;
function check($condition, $message)
{
    global $passed;
    if (!$condition) { throw new RuntimeException($message); }
    $passed++;
}
function failsWith(callable $callback, $expected)
{
    try { $callback(); } catch (RuntimeException $error) {
        check($error->getMessage() === $expected, 'Unexpected error: '.$error->getMessage());
        return;
    }
    throw new RuntimeException('Expected error: '.$expected);
}
function clientFor(array $rows, array $details, $total = null)
{
    return new ShopwareClient(function ($path, $query) use ($rows, $details, $total) {
        if ($path === 'customers') {
            check($query['filter'][0] === ['property' => 'email', 'expression' => '=', 'value' => 'test@example.com'], 'Exact email filter missing');
            return ['success' => true, 'data' => $rows, 'total' => $total ?? count($rows)];
        }
        return ['success' => true, 'data' => $details[$path]];
    });
}
$row = ['id' => 12, 'email' => 'TEST@example.com'];
$detail = $row + [
    'number' => '10012', 'firstname' => 'Ada', 'lastname' => 'Test', 'shopId' => 2,
    'hashPassword' => 'secret', 'paymentData' => ['bankAccount' => 'secret'],
    'defaultBillingAddress' => ['company' => 'Beispiel GmbH', 'phone' => '123',
        'street' => 'Testweg 1', 'zipcode' => '12345', 'city' => 'Teststadt', 'country' => ['name' => 'Deutschland']],
];
$found = clientFor([$row], ['customers/12' => $detail])->findByEmail(' test@example.com ');
check(count($found) === 1 && $found[0]['number'] === '10012' && $found[0]['name'] === 'Ada Test', 'Customer mapping failed');
check($found[0]['company'] === 'Beispiel GmbH' && $found[0]['country'] === 'Deutschland', 'Billing mapping failed');
check(!isset($found[0]['hashPassword']) && !isset($found[0]['paymentData']), 'Sensitive data leaked');
check(clientFor([], [])->findByEmail('test@example.com') === [], 'No match not empty');
$noCall = new ShopwareClient(function () { throw new RuntimeException('Should not call API'); });
check($noCall->findByEmail('') === [] && $noCall->findByEmail('invalid') === [], 'Invalid email accepted');
$second = ['id' => 13, 'email' => 'test@example.com'];
check(count(clientFor([$row, $second], ['customers/12' => $detail, 'customers/13' => $second])->findByEmail('test@example.com')) === 2, 'Multiple matches lost');
failsWith(function () use ($row) {
    clientFor([$row], [], 6)->findByEmail('test@example.com');
}, 'too_many_matches');
failsWith(function () {
    clientFor([['id' => 1, 'email' => 'other@example.com']], [])->findByEmail('test@example.com');
}, 'invalid_response');
failsWith(function () use ($row) {
    clientFor([$row], ['customers/12' => ['id' => 12, 'email' => 'other@example.com']])->findByEmail('test@example.com');
}, 'invalid_response');
failsWith(function () use ($row) {
    clientFor([$row], ['customers/12' => ['id' => 13, 'email' => 'test@example.com']])->findByEmail('test@example.com');
}, 'invalid_response');
failsWith(function () {
    $client = new ShopwareClient(function () { return ['success' => false, 'data' => []]; });
    $client->findByEmail('test@example.com');
}, 'invalid_response');
$legacy = $row + ['billing' => ['company' => 'Alt GmbH', 'number' => 'L12']];
$found = clientFor([$row], ['customers/12' => $legacy])->findByEmail('test@example.com');
check($found[0]['company'] === 'Alt GmbH' && $found[0]['number'] === 'L12', 'Legacy billing mapping failed');
check(BackendLink::customer('https://shop.example.com/sub/backend/', 12) ===
    'https://shop.example.com/sub/backend/?customerSystemsCustomerId=12', 'Backend link incorrect');
foreach (['javascript:alert(1)', 'https://user:pass@example.com/backend', 'http://example.com/backend', 'https://example.com/backend?x=1'] as $url) {
    check(BackendLink::customer($url, 12) === null, 'Unsafe backend URL accepted');
}
check(BackendLink::customer('https://example.com/backend', '../12') === null, 'Invalid customer ID accepted');
failsWith(function () {
    $transport = new ShopwareTransport(['url' => 'http://example.com/api', 'username' => 'x', 'api_key' => 'x', 'auth' => 'basic']);
    $transport('customers', []);
}, 'not_configured');
echo 'OK: '.$passed." checks passed\n";
