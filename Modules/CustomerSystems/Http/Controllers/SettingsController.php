<?php

namespace Modules\CustomerSystems\Http\Controllers;

use App\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CustomerSystems\Services\ConnectionSettings;
use Modules\CustomerSystems\Services\ConnectionError;
use Modules\CustomerSystems\Services\CustomerConnection;
use Modules\CustomerSystems\Services\DiagnosticLog;

class SettingsController extends Controller
{
    private function mailbox($id)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        return Mailbox::findOrFail($id);
    }

    private function current($mailbox)
    {
        try {
            return (new ConnectionSettings())->load($mailbox);
        } catch (\RuntimeException $error) {
            (new DiagnosticLog())->record('settings.load_failed', ['mailbox_id' => $mailbox->id], $error);
            return ConnectionSettings::defaults();
        }
    }

    public function show($id)
    {
        $mailbox = $this->mailbox($id);
        $warning = null;
        try {
            $current = (new ConnectionSettings())->load($mailbox);
        } catch (\RuntimeException $error) {
            $current = ConnectionSettings::defaults();
            $warning = ConnectionError::message('credentials_unreadable');
            (new DiagnosticLog())->record('settings.load_failed', ['mailbox_id' => $mailbox->id], $error);
        }
        return response()->view('customersystems::settings', [
            'mailbox' => $mailbox, 'settings' => ConnectionSettings::publicValues($current),
            'hasSecret' => !empty($current['api_key']), 'warning' => $warning,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function save($id, Request $request)
    {
        $mailbox = $this->mailbox($id);
        try {
            $settings = ConnectionSettings::prepare($request->all(), $this->current($mailbox));
        } catch (\InvalidArgumentException $error) {
            (new DiagnosticLog())->record('settings.rejected', ['mailbox_id' => $id], $error);
            // Never flash the API key/secret into session old input or redisplay it.
            return redirect()->route('mailboxes.customersystems', ['id' => $id])
                ->withErrors(['connection' => $error->getMessage()])
                ->withInput($request->only(['enabled', 'debug_logging', 'url', 'backend_url', 'username']));
        }
        try {
            (new ConnectionSettings())->save($mailbox, $settings);
        } catch (\Throwable $error) {
            (new DiagnosticLog())->record('settings.save_failed', ['mailbox_id' => $id], $error);
            throw $error;
        }
        (new DiagnosticLog())->record('settings.saved', ['mailbox_id' => $id, 'system' => $settings['type'],
            'enabled' => $settings['enabled'], 'debug_logging' => $settings['debug_logging']]);
        return redirect()->route('mailboxes.customersystems', ['id' => $id])
            ->with('flash_success_floating', 'Die Postfach-Verbindung wurde gespeichert.');
    }

    public function test($id, Request $request)
    {
        $mailbox = $this->mailbox($id);
        $start = microtime(true);
        $failure = null;
        try {
            $input = $request->all();
            // A test is allowed before activating the connection and never saves the form.
            $input['enabled'] = '1';
            $settings = ConnectionSettings::prepare($input, $this->current($mailbox));
            CustomerConnection::client($settings)->testConnection();
            $message = 'Verbindung erfolgreich. Anmeldung und Lesezugriff auf Kunden, Bestellungen und Bestellstatus funktionieren. Änderungen bitte anschließend speichern.';
            $ok = true;
        } catch (\InvalidArgumentException $error) {
            $failure = $error;
            $message = $error->getMessage();
            $ok = false;
        } catch (\Throwable $error) {
            $failure = $error;
            $message = ConnectionError::forException($error);
            $ok = false;
        }
        $reference = (new DiagnosticLog())->record('connection_test.'.($ok ? 'succeeded' : 'failed'), [
            'mailbox_id' => $id, 'system' => $settings['type'] ?? null, 'duration_ms' => DiagnosticLog::elapsed($start),
        ], $failure);
        if (!$ok) { $message .= ' Log-Referenz: '.$reference; }
        return response()->json(['ok' => $ok, 'message' => $message, 'reference' => $reference], $ok ? 200 : 422)
            ->header('Cache-Control', 'no-store, private');
    }
}
