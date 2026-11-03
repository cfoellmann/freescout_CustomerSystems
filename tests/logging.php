<?php

require __DIR__.'/controllers.php';

use Modules\CustomerSystems\Services\DiagnosticLog;
use Modules\CustomerSystems\Services\ApiException;
use Modules\CustomerSystems\Services\ConnectionSettings;

$records = [];
$logger = new DiagnosticLog(function ($level, $event, $context) use (&$records) {
    $records[] = compact('level', 'event', 'context');
});
$secret = 'VERY-SENSITIVE-VALUE';
$reference = $logger->record('lookup.failed', [
    'mailbox_id' => 12, 'conversation_id' => '345', 'system' => 'shopware6', 'duration_ms' => 42,
    'email' => $secret, 'url' => $secret, 'api_key' => $secret, 'Authorization' => $secret,
    'request' => ['password' => $secret], 'body' => $secret,
], new ApiException('authentication_failed', 401, 0, 'oauth'));
check((bool) preg_match('/^[a-f0-9]{16}$/', $reference), 'Missing log reference');
check($records[0]['level'] === 'warning' && $records[0]['event'] === 'lookup.failed', 'Incorrect log severity');
check($records[0]['context']['mailbox_id'] === 12 && $records[0]['context']['conversation_id'] === 345, 'Missing identifiers');
check($records[0]['context']['http_status'] === 401 && $records[0]['context']['phase'] === 'oauth', 'Missing API diagnosis');
check(strpos(json_encode($records), $secret) === false, 'Log leaked credentials or customer data');
$logger->record('lookup.failed', ['mailbox_id' => [$secret], 'system' => $secret], new RuntimeException($secret));
check($records[1]['context']['reason'] === 'unexpected_error', 'Unknown exception text leaked');
check(!isset($records[1]['context']['mailbox_id']) && !isset($records[1]['context']['system']), 'Untrusted context leaked');
check(strpos(json_encode($records), $secret) === false, 'Raw exception text leaked');
check($logger->record($secret, []) === null && count($records) === 2, 'Unknown event logged');
$logger->record('connection_test.succeeded', ['duration_ms' => 123]);
check($records[2]['level'] === 'info', 'Successful test must use info severity');
$logger->record('lookup.failed', [], new ApiException('unavailable', 0, 28));
check($records[3]['context']['curl_errno'] === 28, 'cURL timeout code missing');
$logger->record('settings.rejected', [], new InvalidArgumentException($secret));
check($records[4]['context']['reason'] === 'validation_failed' && strpos(json_encode($records), $secret) === false, 'Validation error text leaked');
$broken = new DiagnosticLog(function () use ($secret) { throw new RuntimeException('writer failed '.$secret); });
check(is_string($broken->record('lookup.failed', [], new RuntimeException($secret))), 'Logging failure broke application');
$config = ConnectionSettings::prepare(array_merge($settings, ['debug_logging' => '1']), $settings);
check($config['debug_logging'] === true, 'Diagnostics switch not saved');
check(ConnectionSettings::prepare(array_merge($config, ['debug_logging' => '0']), $config)['debug_logging'] === false, 'Diagnostics switch not disabled');
check(DiagnosticLog::elapsed(microtime(true) - 0.02) >= 20, 'Duration missing');
echo 'OK: '.$passed." total checks including logging redaction passed\n";
