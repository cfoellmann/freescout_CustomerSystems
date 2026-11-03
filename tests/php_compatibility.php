<?php

// Laravel converts reported PHP warnings/deprecations to exceptions. Exercise that behavior
// while autoloading the real module, then close actual cURL handles on a refused local port.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) { throw new ErrorException($message, 0, $severity, $file, $line); }
});
spl_autoload_register(function ($class) {
    $prefix = 'Modules\\CustomerSystems\\';
    if (strpos($class, $prefix) === 0) {
        require __DIR__.'/../Modules/CustomerSystems/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    }
});

use Modules\CustomerSystems\Services\Shopware6Transport;
use Modules\CustomerSystems\Services\JsonHttpClient;
use Modules\CustomerSystems\Services\ShopwareTransport;
use Modules\CustomerSystems\Services\ApiException;

$settings = ['url' => 'https://127.0.0.1:1/api', 'username' => 'test', 'api_key' => 'test', 'auth' => 'basic'];
$transport = new Shopware6Transport($settings, function ($url) {
    return substr($url, -12) === '/oauth/token' ? ['access_token' => 'test.token', 'token_type' => 'Bearer'] : ['data' => []];
});
$result = $transport('search/customer', ['limit' => 1]);
if ($result !== ['data' => []]) { throw new RuntimeException('OAuth smoke test failed'); }

if (function_exists('curl_init')) {
    foreach ([function () { (new JsonHttpClient())->post('https://127.0.0.1:1/api', [], []); },
        function () use ($settings) { (new ShopwareTransport($settings))('customers', []); }] as $request) {
        try {
            $request();
            throw new RuntimeException('Expected refused local connection');
        } catch (ApiException $error) {
            if ($error->curlErrno === 0) { throw new RuntimeException('Expected cURL transport diagnostic'); }
        }
    }
    echo "Actual cURL setup and cleanup passed without PHP warnings/deprecations\n";
}
echo 'PHP compatibility check passed on '.PHP_VERSION."\n";
