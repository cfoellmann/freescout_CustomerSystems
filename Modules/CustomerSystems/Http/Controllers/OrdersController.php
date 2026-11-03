<?php

namespace Modules\CustomerSystems\Http\Controllers;

use App\Conversation;
use Illuminate\Routing\Controller;
use Modules\CustomerSystems\Services\ConnectionSettings;
use Modules\CustomerSystems\Services\CustomerConnection;
use Modules\CustomerSystems\Services\BackendLink;
use Modules\CustomerSystems\Services\DiagnosticLog;

class OrdersController extends Controller
{
    public function show($id, $customerId)
    {
        $conversation = Conversation::findOrFail($id);
        abort_unless(auth()->user()->can('view', $conversation), 403);
        $start = microtime(true);
        $log = new DiagnosticLog();
        $context = ['conversation_id' => $conversation->id, 'mailbox_id' => $conversation->mailbox_id];
        $orders = [];
        $error = null;
        try {
            $settings = (new ConnectionSettings())->load($conversation->mailbox);
            $context['system'] = $settings['type'];
            if (empty($settings['enabled'])) { throw new \RuntimeException('disabled'); }
            // Client queries ANDs customer ID with the current customer's email from this authorized
            // ticket, then validates ownership again. A caller-supplied ID cannot expose other customers.
            $orders = CustomerConnection::client($settings)->latestOrders($customerId, trim((string) $conversation->customer_email));
            foreach ($orders as &$order) {
                $order['backend_url'] = BackendLink::order($settings['backend_url'], $order['id']);
            }
            unset($order);
            if (!empty($settings['debug_logging'])) {
                $log->record('orders.succeeded', array_merge($context, [
                    'match_count' => count($orders), 'duration_ms' => DiagnosticLog::elapsed($start),
                ]));
            }
        } catch (\Throwable $exception) {
            $error = DiagnosticLog::reason($exception);
            $log->record('orders.failed', array_merge($context, ['duration_ms' => DiagnosticLog::elapsed($start)]), $exception);
        }
        return response()->view('customersystems::orders', compact('orders', 'error'))
            ->header('Cache-Control', 'no-store, private');
    }
}
