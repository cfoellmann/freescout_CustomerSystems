<?php

require __DIR__.'/run.php';
require __DIR__.'/../Modules/CustomerSystems/Services/ConnectionSettings.php';
require __DIR__.'/../Modules/CustomerSystems/Services/Shopware6Client.php';
require __DIR__.'/../Modules/CustomerSystems/Services/Shopware6Transport.php';
require __DIR__.'/../Modules/CustomerSystems/Services/JsonHttpClient.php';
require __DIR__.'/../Modules/CustomerSystems/Services/ConnectionError.php';
require __DIR__.'/../Modules/CustomerSystems/Services/ApiException.php';
require __DIR__.'/../Modules/CustomerSystems/Services/DiagnosticLog.php';

use Modules\CustomerSystems\Services\ConnectionSettings;
use Modules\CustomerSystems\Services\Shopware6Client;
use Modules\CustomerSystems\Services\Shopware6Transport;
use Modules\CustomerSystems\Services\BackendLink;
use Modules\CustomerSystems\Services\ConnectionError;

// Framework adapters for exercising mailbox persistence outside a FreeScout installation.
function encrypt($value) { return 'encrypted:'.base64_encode(serialize($value)); }
function decrypt($value) {
    if (strpos($value, 'encrypted:') !== 0) { throw new RuntimeException('Invalid ciphertext'); }
    return unserialize(base64_decode(substr($value, 10)));
}
function config($key, $default = null) { return $GLOBALS['legacy_config'] ?? $default; }
class TestMailbox {
    public $meta = [];
    public $saved = false;
    public function getMeta($key) { return $this->meta[$key] ?? null; }
    public function setMeta($key, $value) { $this->meta[$key] = $value; }
    public function save() { $this->saved = true; }
}

$settings = ConnectionSettings::defaults();
$settings = array_merge($settings, ['enabled' => true, 'url' => 'https://shop.example.com/api', 'username' => 'reader', 'api_key' => 'secret']);
check(ConnectionSettings::prepare(array_merge($settings, ['api_key' => '']), $settings)['api_key'] === 'secret', 'Empty key should preserve saved key');
foreach (['type' => 'shopware6', 'url' => 'https://other.example.com/api', 'username' => 'another-user', 'auth' => 'basic'] as $key => $value) {
    try {
        ConnectionSettings::prepare(array_merge($settings, [$key => $value, 'api_key' => '']), $settings);
        throw new RuntimeException('Changed connection reused credentials');
    } catch (InvalidArgumentException $expected) { check(true, 'Changed target must require fresh credentials'); }
}
$disabled = ConnectionSettings::prepare(array_merge($settings, ['enabled' => '0', 'clear_secret' => '1']), $settings);
check(!$disabled['enabled'] && $disabled['api_key'] === '', 'Disable and clear credentials failed');
check(!array_key_exists('api_key', ConnectionSettings::publicValues($settings)), 'Secret exposed to view');
foreach (['http://shop.example.com/api', 'https://user:pass@shop.example.com/api', 'https://shop.example.com/api?query=x', 'javascript:alert(1)'] as $url) {
    try {
        ConnectionSettings::prepare(array_merge($settings, ['url' => $url]), $settings);
        throw new RuntimeException('Unsafe settings URL accepted');
    } catch (InvalidArgumentException $expected) { check(true, 'Unsafe URL must be rejected'); }
}
$store = new ConnectionSettings();
$mailboxA = new TestMailbox();
$mailboxB = new TestMailbox();
$store->save($mailboxA, $settings);
check($mailboxA->saved && is_string($mailboxA->meta[ConnectionSettings::META_KEY]), 'Settings not stored through encryption adapter');
check(strpos($mailboxA->meta[ConnectionSettings::META_KEY], 'secret') === false, 'Clear text secret stored');
check($store->load($mailboxA) === $settings, 'Stored configuration not loaded');
check(!$store->load($mailboxB)['enabled'], 'Configuration crossed mailbox boundary');
$GLOBALS['legacy_config'] = ['url' => 'https://legacy.example.com/api', 'api_key' => 'legacy'];
check($store->load($mailboxB)['enabled'], 'Legacy configuration fallback missing');
check($store->load($mailboxA)['url'] === $settings['url'], 'Legacy configuration overrode mailbox');
$store->save($mailboxB, $disabled);
check(!$store->load($mailboxB)['enabled'], 'Explicit disable must override environment fallback');
$mailboxB->meta[ConnectionSettings::META_KEY] = 'broken';
failsWith(function () use ($store, $mailboxB) { $store->load($mailboxB); }, 'credentials_unreadable');

$id = '0123456789abcdef0123456789abcdef';
$customer = ['id' => $id, 'email' => 'TEST@example.com', 'customerNumber' => 'SW6001', 'firstName' => 'Ada', 'lastName' => 'Test',
    'salesChannelId' => 'fedcba9876543210fedcba9876543210', 'password' => 'never expose',
    'defaultBillingAddress' => ['company' => 'SW6 GmbH', 'phoneNumber' => '555', 'street' => 'Testweg 6', 'zipcode' => '12345', 'city' => 'Berlin', 'country' => ['translated' => ['name' => 'Deutschland']]]];
$client = new Shopware6Client(function ($path, $body) use ($customer) {
    check($path === 'search/customer', 'Incorrect Shopware 6 endpoint');
    check($body['filter'][0] === ['type' => 'equals', 'field' => 'email', 'value' => 'test@example.com'], 'Incorrect SW6 email filter');
    check(isset($body['associations']['defaultBillingAddress']['associations']['country']), 'Billing association missing');
    return ['total' => 1, 'data' => [$customer]];
});
$found = $client->findByEmail('test@example.com');
check($found[0]['number'] === 'SW6001' && $found[0]['company'] === 'SW6 GmbH' && $found[0]['country'] === 'Deutschland', 'SW6 mapping failed');
check(!isset($found[0]['password']) && count($found[0]) === 11, 'SW6 field allowlist failed');
foreach ([['total' => 6, 'data' => []], ['total' => 1, 'data' => [array_merge($customer, ['email' => 'other@example.com'])]], ['total' => 1, 'data' => [array_merge($customer, ['id' => 'bad'])]], ['errors' => []]] as $response) {
    failsWith(function () use ($response) {
        (new Shopware6Client(function () use ($response) { return $response; }))->findByEmail('test@example.com');
    }, ($response['total'] ?? 0) === 6 ? 'too_many_matches' : 'invalid_response');
}
$empty = new Shopware6Client(function () { return ['total' => 0, 'data' => []]; });
check($empty->findByEmail('test@example.com') === [], 'SW6 empty lookup failed');
$never = new Shopware6Client(function () { throw new RuntimeException('Unexpected API call'); });
check($never->findByEmail('invalid') === [], 'SW6 invalid email should not call API');
$secondCustomer = array_merge($customer, ['id' => '1123456789abcdef0123456789abcdef']);
$multiple = new Shopware6Client(function () use ($customer, $secondCustomer) { return ['total' => 2, 'data' => [$customer, $secondCustomer]]; });
check(count($multiple->findByEmail('test@example.com')) === 2, 'SW6 multiple matches lost');
$requests = [];
$transport = new Shopware6Transport($settings, function ($url, $body, $headers) use (&$requests) {
    $requests[] = compact('url', 'body', 'headers');
    return count($requests) === 1 ? ['access_token' => 'test.token', 'token_type' => 'Bearer'] : ['data' => []];
});
$transport('search/customer', ['limit' => 1]);
$transport('search/customer', ['limit' => 1]);
check(count($requests) === 3, 'OAuth token not reused within request');
check($requests[0]['url'] === 'https://shop.example.com/api/oauth/token' && $requests[0]['body']['grant_type'] === 'client_credentials', 'OAuth endpoint or grant incorrect');
check($requests[0]['body']['client_id'] === 'reader' && $requests[0]['body']['client_secret'] === 'secret', 'OAuth credentials missing');
check($requests[1]['headers'] === ['Authorization: Bearer test.token'] && !isset($requests[1]['body']['client_secret']), 'Search token/secret separation failed');
failsWith(function () use ($settings) {
    $transport = new Shopware6Transport($settings, function () { return ['access_token' => "bad\r\nInjected: yes", 'token_type' => 'Bearer']; });
    $transport('search/customer', []);
}, 'invalid_response');
check(BackendLink::customer('https://shop.example.com/admin', $id, 'shopware6') === 'https://shop.example.com/admin#/sw/customer/detail/'.$id.'/base', 'SW6 profile link wrong');
check(BackendLink::customer('https://shop.example.com/admin', '123', 'shopware6') === null, 'Invalid SW6 id accepted');
$probe = new Shopware6Client(function ($path, $body) {
    check($body['includes'] === ['customer' => ['id']], 'Probe should request only customer IDs');
    return ['data' => []];
});
$probe->testConnection();
check(ConnectionError::message('unexpected-secret') === ConnectionError::message('unavailable'), 'Error could expose upstream response');
echo 'OK: '.$passed." total checks passed\n";
