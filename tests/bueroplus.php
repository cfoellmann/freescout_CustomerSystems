<?php

require __DIR__.'/controllers.php';
require __DIR__.'/../Modules/CustomerSystems/Services/BueroPlusClient.php';
require __DIR__.'/../Modules/CustomerSystems/Http/Controllers/BueroPlusController.php';

use Modules\CustomerSystems\Services\BueroPlusSettings;
use Modules\CustomerSystems\Services\BueroPlusClient;
use Modules\CustomerSystems\Services\ApiException;
use Modules\CustomerSystems\Services\ConnectionError;
use Modules\CustomerSystems\Services\DiagnosticLog;
use Modules\CustomerSystems\Http\Controllers\BueroPlusController;
use Illuminate\Http\Request;

$config = ['url' => 'https://erp.example.com/microtech/erp/test', 'client_id' => 'Client_123', 'client_secret' => 'Secret_123'];
$store = new BueroPlusSettings();
$first = \App\Mailbox::$records[1];
$other = new TestMailbox();
$shop = $first->getMeta(\Modules\CustomerSystems\Services\ConnectionSettings::META_KEY);
$store->save($first, $config);
check($store->load($first) === $config && $store->load($other) === BueroPlusSettings::defaults(), 'ERP settings crossed mailboxes');
check($first->getMeta(\Modules\CustomerSystems\Services\ConnectionSettings::META_KEY) === $shop, 'ERP save changed Shopware');
check(strpos($first->getMeta(BueroPlusSettings::META_KEY), $config['client_secret']) === false, 'Secret persisted in clear');
check(!isset(BueroPlusSettings::publicValues($config)['client_secret']), 'Public settings contain secret');
check(BueroPlusSettings::prepare(array_merge($config, ['client_secret' => '']), $config)['client_secret'] === 'Secret_123', 'Blank secret not retained');
foreach (['url' => 'https://other.example.com', 'client_id' => 'OtherClient'] as $key => $value) {
    $changed = BueroPlusSettings::prepare(array_merge($config, [$key => $value, 'client_secret' => '']), $config);
    check($changed['client_secret'] === '', 'Retained secret across changed target/client');
}
check(BueroPlusSettings::prepare(array_merge($config, ['clear_secret' => '1']), $config)['client_secret'] === '', 'Cannot clear secret');
foreach (['http://erp.example.com', 'https://localhost', 'https://127.0.0.1', 'https://[::1]', 'https://user:pass@erp.example.com', 'https://erp.example.com?x=1', $config['url'].'/token', $config['url'].'/graphql/v1'] as $bad) {
    try { BueroPlusSettings::prepare(array_merge($config, ['url' => $bad]), $config, true); check(false, 'Unsafe/wrong URL accepted'); }
    catch (\InvalidArgumentException $expected) { check(true, 'URL rejected'); }
}

class BueroTestHttp {
    public $calls = [];
    public $token = ['access_token' => 'test-token', 'token_type' => 'Bearer'];
    public $graph = ['data' => ['__typename' => 'Query']];
    public $failure;
    public function postForm($url, $payload, $headers) {
        $this->calls[] = [$url, $payload, $headers];
        if ($this->failure) { throw $this->failure; }
        return $this->token;
    }
    public function post($url, $payload, $headers) { $this->calls[] = [$url, $payload, $headers]; return $this->graph; }
}
$http = new BueroTestHttp();
check((new BueroPlusClient($config, $http))->testConnection(), 'Connection probe failed');
check($http->calls[0] === [$config['url'].'/token', ['grant_type' => 'client_credentials', 'scope' => 'graphql'], ['Authorization: Basic '.base64_encode('Client_123:Secret_123')]], 'OAuth request differs from microtech contract');
check($http->calls[1] === [$config['url'].'/graphql/v1', ['query' => 'query FreeScoutConnectionTest { __typename }'], ['Authorization: Bearer test-token']], 'Probe reads ERP data or uses wrong route/authentication');
$http = new BueroTestHttp();
$http->token['access_token'] = "token\r\nInjected: header";
failsWith(function () use ($http, $config) { (new BueroPlusClient($config, $http))->testConnection(); }, 'invalid_response');
check(count($http->calls) === 1, 'Invalid token sent to GraphQL');
$http = new BueroTestHttp();
$http->failure = new ApiException('authentication_failed', 401, 0, 'oauth');
failsWith(function () use ($http, $config) { (new BueroPlusClient($config, $http))->testConnection(); }, 'authentication_failed');
check(count($http->calls) === 1 && strpos(ConnectionError::forBueroPlus($http->failure), 'Client-ID') !== false, 'OAuth failure hidden');
$http = new BueroTestHttp();
$http->graph = ['data' => ['__typename' => 'Query'], 'errors' => [['message' => 'secret ERP content']]];
failsWith(function () use ($http, $config) { (new BueroPlusClient($config, $http))->testConnection(); }, 'graphql_failed');
$http->graph = ['data' => null];
failsWith(function () use ($http, $config) { (new BueroPlusClient($config, $http))->testConnection(); }, 'invalid_response');

$controller = new BueroPlusController();
$GLOBALS['test_user']->admin = false;
foreach (['save', 'test'] as $method) {
    failsWith(function () use ($controller, $method, $config) { $controller->$method(1, new Request($config)); }, 'http_403');
}
$GLOBALS['test_user']->admin = true;
$before = $first->meta;
$response = $controller->test(1, new Request(['url' => 'http://invalid', 'client_secret' => 'DoNotLog']));
check(!$response->data['ok'] && isset($response->data['reference']) && $first->meta === $before, 'Test persisted input or hid failure');
$response = $controller->save(1, new Request(['url' => 'http://invalid', 'client_secret' => 'DoNotFlash']));
check(count($response->errors) === 1 && $response->old === [] && $first->meta === $before, 'Rejected save exposed secret/changed settings');
$response = (new \Modules\CustomerSystems\Http\Controllers\SettingsController())->show(1);
check($response->data['bueroHasSecret'] && !isset($response->data['bueroSettings']['client_secret']), 'Settings response exposed ERP secret');
$controller->save(1, new Request(array_merge($config, ['client_secret' => '', 'clear_secret' => '1'])));
check($store->load($first)['client_secret'] === '' && $first->getMeta(\Modules\CustomerSystems\Services\ConnectionSettings::META_KEY) === $shop, 'Controller delete affected wrong connection');
$captured = [];
(new DiagnosticLog(function ($level, $event, $context) use (&$captured) { $captured = $context; }))
    ->record('connection_test.failed', ['system' => 'bueroplus', 'client_secret' => 'DoNotLog', 'response' => $http->graph], new ApiException('graphql_failed', 200, 0, 'api'));
check($captured['system'] === 'bueroplus' && $captured['reason'] === 'graphql_failed' && !isset($captured['client_secret'], $captured['response']), 'ERP logging leaked content');
echo 'OK: '.$passed." checks including büro+ preparation passed\n";
