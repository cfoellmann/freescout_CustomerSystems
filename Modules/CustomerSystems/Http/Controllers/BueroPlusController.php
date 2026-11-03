<?php

namespace Modules\CustomerSystems\Http\Controllers;

use App\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CustomerSystems\Services\BueroPlusSettings;
use Modules\CustomerSystems\Services\BueroPlusClient;
use Modules\CustomerSystems\Services\ConnectionError;
use Modules\CustomerSystems\Services\DiagnosticLog;

class BueroPlusController extends Controller
{
    private function mailbox($id)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        return Mailbox::findOrFail($id);
    }

    public function save($id, Request $request)
    {
        $mailbox = $this->mailbox($id);
        try {
            $store = new BueroPlusSettings();
            $settings = BueroPlusSettings::prepare($request->all(), $store->load($mailbox));
            $store->save($mailbox, $settings);
        } catch (\Throwable $error) {
            (new DiagnosticLog())->record('settings.save_failed', ['mailbox_id' => $id, 'system' => 'bueroplus'], $error);
            return redirect()->route('mailboxes.customersystems', ['id' => $id])
                ->withErrors(['bueroplus' => $error instanceof \InvalidArgumentException
                    ? $error->getMessage() : ConnectionError::forBueroPlus($error)]);
        }
        (new DiagnosticLog())->record('settings.saved', ['mailbox_id' => $id, 'system' => 'bueroplus']);
        return redirect()->route('mailboxes.customersystems', ['id' => $id])
            ->with('flash_success_floating', 'büro+-Zugang gespeichert. Der Kundenabruf ist noch nicht verfügbar.');
    }

    public function test($id, Request $request)
    {
        $mailbox = $this->mailbox($id);
        $start = microtime(true);
        $failure = null;
        try {
            $settings = BueroPlusSettings::prepare($request->all(), (new BueroPlusSettings())->load($mailbox), true);
            (new BueroPlusClient($settings))->testConnection();
            $ok = true;
            $message = 'OAuth-Anmeldung und GraphQL-Endpunkt funktionieren. Kundenrechte und E-Mail-Zuordnung sind damit noch nicht geprüft. Änderungen bitte separat speichern.';
        } catch (\Throwable $error) {
            $ok = false;
            $failure = $error;
            $message = $error instanceof \InvalidArgumentException ? $error->getMessage() : ConnectionError::forBueroPlus($error);
        }
        $reference = (new DiagnosticLog())->record('connection_test.'.($ok ? 'succeeded' : 'failed'), [
            'mailbox_id' => $id, 'system' => 'bueroplus', 'duration_ms' => DiagnosticLog::elapsed($start),
        ], $failure);
        if (!$ok) { $message .= ' Log-Referenz: '.$reference; }
        return response()->json(['ok' => $ok, 'message' => $message, 'reference' => $reference], $ok ? 200 : 422)
            ->header('Cache-Control', 'no-store, private');
    }
}
