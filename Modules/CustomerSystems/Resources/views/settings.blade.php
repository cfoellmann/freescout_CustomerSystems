@extends('layouts.app')
@section('title_full', 'Kundenprofil Abruf - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">Kundenprofil Abruf</div>
    @include('partials/flash_messages')
    <div class="row-container">
        <div class="row"><div class="col-xs-12 col-lg-9">
            <p>Kundenprofile für <strong>{{ $mailbox->name }}</strong> anhand der Ticket-E-Mail abrufen.</p>
            @if ($warning)<div class="alert alert-warning">{{ $warning }}</div>@endif
            @if ($errors->has('connection'))<div class="alert alert-danger">{{ $errors->first('connection') }}</div>@endif
            <form class="customer-systems-settings form-horizontal" method="POST"
                  action="{{ route('mailboxes.customersystems.save', ['id' => $mailbox->id]) }}"
                  data-test-url="{{ route('mailboxes.customersystems.test', ['id' => $mailbox->id]) }}">
                {{ csrf_field() }}
                <input type="hidden" name="enabled" value="0">
                <h3>Shopware-Verbindung</h3>
                <div class="form-group"><div class="col-sm-offset-3 col-sm-9"><div class="checkbox">
                    <label><input type="checkbox" name="enabled" value="1" @if (old('enabled', $settings['enabled'])) checked @endif> Für dieses Postfach aktivieren</label>
                </div></div></div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" for="cs-type">System</label>
                    <div class="col-sm-9"><select id="cs-type" name="type" class="form-control">
                        <option value="shopware5" @if (old('type', $settings['type']) === 'shopware5') selected @endif>Shopware 5</option>
                        <option value="shopware6" @if (old('type', $settings['type']) === 'shopware6') selected @endif>Shopware 6</option>
                    </select></div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" for="cs-url">API-URL</label>
                    <div class="col-sm-9"><input id="cs-url" type="url" name="url" class="form-control" maxlength="2048" placeholder="https://shop.example.com/api" value="{{ old('url', $settings['url']) }}">
                        <p class="help-block">HTTPS-Adresse inklusive <code>/api</code>, auch bei Shopware 6.</p></div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" for="cs-backend">Backend-URL</label>
                    <div class="col-sm-9"><input id="cs-backend" type="url" name="backend_url" class="form-control" maxlength="2048" value="{{ old('backend_url', $settings['backend_url']) }}">
                        <p class="help-block"><span data-sw="shopware5">Optional: https://shop.example.com/backend. Für Kunden-Direktlinks ist das Begleitplugin nötig.</span><span data-sw="shopware6">Optional: https://shop.example.com/admin. Kundenprofile werden direkt in der Administration geöffnet.</span></p></div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" for="cs-user"><span data-sw="shopware5">API-Benutzer</span><span data-sw="shopware6">Access-Key-ID</span></label>
                    <div class="col-sm-9"><input id="cs-user" type="text" name="username" class="form-control" maxlength="2048" autocomplete="off" value="{{ old('username', $settings['username']) }}"></div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" for="cs-key"><span data-sw="shopware5">API-Key</span><span data-sw="shopware6">Secret Access Key</span></label>
                    <div class="col-sm-9"><input id="cs-key" type="password" name="api_key" class="form-control" maxlength="4096" autocomplete="new-password" value="">
                        <p class="help-block">{{ $hasSecret ? 'Ein Schlüssel ist gespeichert. Leer lassen, um ihn beizubehalten.' : 'Noch kein Schlüssel gespeichert.' }} Bei geändertem System, API-Ziel oder Benutzer bitte neu eingeben.</p>
                        <div class="checkbox"><label><input type="checkbox" name="clear_secret" value="1"> Gespeicherten Schlüssel löschen (Verbindung vorher deaktivieren)</label></div>
                    </div>
                </div>
                <div class="form-group" data-sw="shopware5">
                    <label class="col-sm-3 control-label" for="cs-auth">Authentifizierung</label>
                    <div class="col-sm-9"><select id="cs-auth" name="auth" class="form-control">
                        <option value="digest" @if (old('auth', $settings['auth']) === 'digest') selected @endif>Digest</option>
                        <option value="basic" @if (old('auth', $settings['auth']) === 'basic') selected @endif>Basic (ab Shopware 5.3.2)</option>
                    </select></div>
                </div>
                <div class="form-group" data-sw="shopware6"><div class="col-sm-offset-3 col-sm-9"><p class="help-block">Unter Shopware → Einstellungen → System → Integrationen einen Zugang mit Leserechten auf Kunden und die zugehörigen Adressen, Länder und Verkaufskanäle anlegen. Die Anmeldung erfolgt über OAuth 2.0.</p></div></div>
                <div class="form-group"><div class="col-sm-offset-3 col-sm-9">
                    <input type="hidden" name="debug_logging" value="0">
                    <div class="checkbox"><label><input type="checkbox" name="debug_logging" value="1" @if (old('debug_logging', $settings['debug_logging'] ?? false)) checked @endif> Diagnose-Logging: auch erfolgreiche Kundenabrufe protokollieren</label></div>
                    <p class="help-block">Fehler, Verbindungstests und Konfigurationsänderungen werden immer protokolliert. Logdateien: <code>storage/logs/customer-systems-YYYY-MM-DD.log</code>, maximal 14 Tagesdateien. Keine Zugangsdaten oder Kundeninhalte.</p>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                    <button type="button" class="btn btn-default customer-systems-test">Verbindung testen</button>
                    <p class="help-block">Der Test prüft die aktuellen Formulareingaben und speichert sie nicht.</p>
                    <div class="customer-systems-test-result" role="status" aria-live="polite"></div>
                </div></div>
            </form>
            <hr>
            <div class="text-muted" aria-disabled="true">
                <h3><span class="glyphicon glyphicon-link" aria-hidden="true"></span> büro+ <span class="label label-default">Noch nicht verfügbar</span></h3>
                <p>Die ERP-Verbindung wird später ergänzt.</p>
                <button type="button" class="btn btn-default" disabled>büro+ verbinden</button>
            </div>
        </div></div>
    </div>
@endsection
