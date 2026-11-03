<?php

namespace Modules\CustomerSystems\Services;

class DiagnosticLog
{
    private $writer;

    public function __construct(?callable $writer = null)
    {
        $this->writer = $writer;
    }

    public static function reason(\Throwable $error)
    {
        if ($error instanceof \InvalidArgumentException) { return 'validation_failed'; }
        $known = ['disabled', 'no_email', 'credentials_unreadable', 'authentication_failed', 'permission_denied',
            'not_configured', 'curl_missing', 'too_many_matches', 'invalid_response', 'unavailable', 'graphql_failed'];
        return in_array($error->getMessage(), $known, true) ? $error->getMessage() : 'unexpected_error';
    }

    public static function details(\Throwable $error)
    {
        $details = [];
        foreach (['ErrorException', 'TypeError', 'ParseError', 'Error', 'RuntimeException', 'Exception'] as $type) {
            if ($error instanceof $type) { $details['exception_type'] = $type; break; }
        }
        $details['exception_type'] = $details['exception_type'] ?? 'Throwable';
        $root = str_replace('\\', '/', dirname(__DIR__)).'/';
        $file = str_replace('\\', '/', $error->getFile());
        // Reveal a module filename and line only, never the server's full filesystem path.
        $details['source_file'] = strpos($file, $root) === 0 ? basename($file) : 'external';
        $details['source_line'] = max(0, (int) $error->getLine());
        $details['php_version'] = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
        if ($error instanceof \ErrorException && $error->getSeverity() === E_DEPRECATED) {
            $details['php_error'] = 'deprecated_api';
        }
        return $details;
    }

    public function record($event, array $context = [], ?\Throwable $error = null)
    {
        // Only known event names and typed fields may reach the logger. Never pass a request,
        // exception, API response, URL, email or connection configuration to Monolog.
        $events = ['lookup.succeeded', 'lookup.failed', 'lookup.skipped', 'connection_test.succeeded',
            'connection_test.failed', 'settings.saved', 'settings.rejected', 'settings.load_failed', 'settings.save_failed',
            'orders.succeeded', 'orders.failed'];
        if (!in_array($event, $events, true)) { return null; }
        $safe = ['reference' => substr(hash('sha256', uniqid('', true)), 0, 16)];
        foreach (['mailbox_id', 'conversation_id', 'duration_ms', 'match_count'] as $key) {
            if (isset($context[$key]) && (is_int($context[$key]) || (is_string($context[$key]) && ctype_digit($context[$key])))) {
                $safe[$key] = max(0, (int) $context[$key]);
            }
        }
        if (in_array($context['system'] ?? null, ['shopware6', 'bueroplus'], true)) {
            $safe['system'] = $context['system'];
        }
        foreach (['enabled', 'debug_logging'] as $key) {
            if (isset($context[$key]) && is_bool($context[$key])) { $safe[$key] = $context[$key]; }
        }
        if ($error) {
            $safe['reason'] = self::reason($error);
            if ($safe['reason'] === 'unexpected_error') {
                $safe = array_merge($safe, self::details($error));
            }
            if ($error instanceof ApiException) {
                $safe['http_status'] = $error->httpStatus;
                $safe['curl_errno'] = $error->curlErrno;
                $safe['phase'] = in_array($error->phase, ['oauth', 'orders'], true) ? $error->phase : 'api';
            }
        }
        $level = $error && !in_array($safe['reason'], ['disabled', 'no_email'], true) ? 'warning' : 'info';
        try {
            if ($this->writer) {
                call_user_func($this->writer, $level, $event, $safe);
            } else {
                $logger = new \Monolog\Logger('customer-systems');
                // Keep at most 14 daily files, with restrictive file permissions.
                $handler = new \Monolog\Handler\RotatingFileHandler(storage_path('logs/customer-systems.log'), 14, \Monolog\Logger::INFO, true, 0600, true);
                $logger->pushHandler($handler);
                $logger->log($level, $event, $safe);
                $logger->close();
            }
        } catch (\Throwable $loggingError) {
            // Logging must not break a ticket or connection test; expose no filesystem error text.
            try { \Log::warning('Customer Systems: diagnostic log unavailable', ['reference' => $safe['reference']]); }
            catch (\Throwable $ignored) { /* Preserve the application response. */ }
        }
        return $safe['reference'];
    }

    public static function elapsed($start)
    {
        return max(0, (int) round((microtime(true) - $start) * 1000));
    }
}
