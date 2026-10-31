<?php

namespace Modules\CustomerSystems\Http\Controllers;

use App\Conversation;
use Illuminate\Routing\Controller;
use Modules\CustomerSystems\Services\ShopwareClient;
use Modules\CustomerSystems\Services\ShopwareTransport;
use Modules\CustomerSystems\Services\BackendLink;

class SidebarController extends Controller
{
    public function show($id)
    {
        $conversation = Conversation::findOrFail($id);
        abort_unless(auth()->user()->can('view', $conversation), 403);
        // Resolve from the authorized ticket; never accept a caller-supplied email.
        $email = trim((string) $conversation->customer_email);
        $customers = [];
        $error = null;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'no_email';
        } else {
            try {
                $client = new ShopwareClient(new ShopwareTransport(config('customersystems.shopware', [])));
                $customers = $client->findByEmail($email);
                foreach ($customers as &$customer) {
                    $customer['backend_url'] = BackendLink::customer(
                        config('customersystems.shopware.backend_url', ''), $customer['id']
                    );
                }
                unset($customer);
            } catch (\Throwable $exception) {
                $known = ['not_configured', 'curl_missing', 'too_many_matches', 'invalid_response', 'unavailable'];
                $error = in_array($exception->getMessage(), $known, true)
                    ? $exception->getMessage() : 'unavailable';
                \Log::warning('Customer Systems: Shopware lookup failed', ['reason' => $error]);
            }
        }
        return response()->view('customersystems::results', compact('customers', 'error', 'email'))
            ->header('Cache-Control', 'no-store, private');
    }
}
