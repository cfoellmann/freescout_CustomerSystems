<form class="customer-systems-settings form-horizontal" method="POST"
      action="{{ route('mailboxes.customersystems.bueroplus.save', ['id' => $mailbox->id]) }}"
      data-test-url="{{ route('mailboxes.customersystems.bueroplus.test', ['id' => $mailbox->id]) }}">
    {{ csrf_field() }}
    <h3>büro+ <span class="label label-default">In Vorbereitung</span></h3>
    <p>OAuth-Zugang vorbereiten und testen. Die Kundensuche über E-Mail und die Anzeige von ERP-Daten sind noch nicht verfügbar.</p>
    @if ($bueroWarning)<div class="alert alert-warning">{{ $bueroWarning }}</div>@endif
    @if ($errors->has('bueroplus'))<div class="alert alert-danger">{{ $errors->first('bueroplus') }}</div>@endif
    <div class="form-group">
        <label class="col-sm-3 control-label" for="bp-url">Basis-URL</label>
        <div class="col-sm-9"><input id="bp-url" type="url" name="url" class="form-control" maxlength="2048" value="{{ $bueroSettings['url'] }}" placeholder="https://erp.example.com/microtech/erp/serverpfad">
            <p class="help-block">HTTPS-Adresse mit DNS-Namen inklusive des eingerichteten Serverpfads, ohne <code>/token</code> oder <code>/graphql/v1</code>. Vom FreeScout-Server erreichbar.</p></div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label" for="bp-client">Client-ID</label>
        <div class="col-sm-9"><input id="bp-client" type="text" name="client_id" class="form-control" maxlength="2048" autocomplete="off" value="{{ $bueroSettings['client_id'] }}"></div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label" for="bp-secret">Client-Secret</label>
        <div class="col-sm-9"><input id="bp-secret" type="password" name="client_secret" class="form-control" maxlength="2048" autocomplete="new-password" value="">
            <p class="help-block">{{ $bueroHasSecret ? 'Ein Secret ist gespeichert. Leer lassen, um es beizubehalten.' : 'Noch kein Secret gespeichert.' }} Bei geänderter Basis-URL oder Client-ID erneut eingeben.</p>
            <div class="checkbox"><label><input type="checkbox" name="clear_secret" value="1"> Gespeichertes Secret löschen</label></div>
        </div>
    </div>
    <div class="form-group"><div class="col-sm-offset-3 col-sm-9">
        <p class="help-block">In microtech eine vertrauliche Anwendung mit verknüpftem Benutzer und GraphQL-Zugriff anlegen. Die Einrichtung ist in der README beschrieben.</p>
        <button type="submit" class="btn btn-primary">büro+-Zugang speichern</button>
        <button type="button" class="btn btn-default customer-systems-test">Verbindung testen</button>
        <p class="help-block">Der Test nutzt die aktuellen Eingaben ohne sie zu speichern. Er prüft OAuth und den GraphQL-Endpunkt, noch keine Kundenrechte. Fehler und Tests erscheinen im Modul-Log.</p>
        <div class="customer-systems-test-result" role="status" aria-live="polite"></div>
    </div></div>
</form>
