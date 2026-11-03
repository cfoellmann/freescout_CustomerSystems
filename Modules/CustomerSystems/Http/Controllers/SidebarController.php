<?php

namespace Modules\CustomerSystems\Http\Controllers;

use App\Conversation;
use Illuminate\Routing\Controller;
use Modules\CustomerSystems\Services\BackendLink;
use Modules\CustomerSystems\Services\ConnectionSettings;
use Modules\CustomerSystems\Services\CustomerConnection;
use Modules\CustomerSystems\Services\DiagnosticLog;

class SidebarController extends Controller
{
    public function show($id)
    {
        $conversation = Conversation::findOrFail($id);
        abort_unless(auth()->user()->can('view', $conversation), 403);
        $start = microtime(true);
        $log = new DiagnosticLog();
        $context = ['conversation_id' => $conversation->id, 'mailbox_id' => $conversation->mailbox_id];
        // Resolve from the authorized ticket; never accept a caller-supplied email.
        $email = trim((string) $conversation->customer_email);
        $customers = [];
        $error = null;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'no_email';
            $log->record('lookup.skipped', $context, new \RuntimeException('no_email'));
        } else {
            try {
                $settings = (new ConnectionSettings())->load($conversation->mailbox);
                $context['system'] = $settings['type'];
                if (empty($settings['enabled'])) {
                    throw new \RuntimeException('disabled');
                }
                $client = CustomerConnection::client($settings);
                $customers = $client->findByEmail($email);
                foreach ($customers as &$customer) {
                    $customer['backend_url'] = BackendLink::customer(
                        $settings['backend_url'], $customer['id'], $settings['type']
                    );
                }
                unset($customer);
                if (!empty($settings['debug_logging'])) {
                    $log->record('lookup.succeeded', array_merge($context, [
                        'duration_ms' => DiagnosticLog::elapsed($start), 'match_count' => count($customers),
                    ]));
                }
            } catch (\Throwable $exception) {
                $known = ['disabled', 'credentials_unreadable', 'authentication_failed', 'permission_denied', 'not_configured', 'curl_missing', 'too_many_matches', 'invalid_response', 'unavailable'];
                $error = in_array($exception->getMessage(), $known, true)
                    ? $exception->getMessage() : 'unavailable';
                if ($error !== 'disabled' || !empty($settings['debug_logging'])) {
                    $log->record($error === 'disabled' ? 'lookup.skipped' : 'lookup.failed',
                        array_merge($context, ['duration_ms' => DiagnosticLog::elapsed($start)]), $exception);
                }
            }
        }
        return response()->view('customersystems::results', compact('customers', 'error', 'email'))
            ->header('Cache-Control', 'no-store, private');
    }
}
