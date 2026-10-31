# FreeScout Customer Systems

Erste Implementierung eines FreeScout-Moduls für **Shopware 5**. Die Zuordnung erfolgt über die `customer_email` des geöffneten Tickets. **büro+ ist auf Wunsch zurückgestellt und noch nicht angebunden.**

Angezeigt werden Kundennummer, Shopware-ID, Shop-ID, Firma, Name, E-Mail, Telefon und Rechnungsanschrift. Die Kundendaten laden nach dem Ticket asynchron. Mehrere Treffer werden einzeln angezeigt; bei mehr als fünf Treffern fordert das Modul zur Prüfung im Shop auf. Es verändert keine Daten und speichert keine Shopware-Kundendaten in FreeScout.

## Installation in FreeScout

1. `Modules/CustomerSystems` in das gleichnamige `Modules`-Verzeichnis der FreeScout-Installation kopieren. Der Ordner muss `CustomerSystems` heißen.
2. Die Werte aus `.env.example` in die serverseitige FreeScout-`.env` übernehmen. `SHOPWARE_URL` endet auf `/api`; `SHOPWARE_BACKEND_URL` auf `/backend`. Unterverzeichnisse sind möglich.
3. In Shopware einen API-Benutzer mit Leserechten auf Kunden einrichten und dessen Benutzername und API-Key hinterlegen. Standard ist Digest-Authentifizierung; Basic ist ab Shopware 5.3.2 möglich. HTTPS und PHP-cURL sind erforderlich.
4. In FreeScout **Verwalten → Module** das Modul aktivieren. Danach den FreeScout-Cache über die Systemwerkzeuge leeren. Bei gecachter Laravel-Konfiguration diese neu erzeugen lassen.
5. Für die Backend-Direktlinks zusätzlich das unten beschriebene Shopware-Plugin installieren.

Ein FreeScout-API-Modul ist hierfür nicht notwendig. Der Modulendpunkt verwendet die Anmeldung und die bestehende FreeScout-Ticketberechtigung. Er nimmt keine frei übergebene E-Mail entgegen. API-Schlüssel erscheinen nicht im Browser. Alle angezeigten Kundenwerte werden von Blade escaped. API-Fehlertexte und sensible API-Felder werden nicht weitergegeben.

## Self-Updates und Releases

Das Modul verwendet FreeScouts eingebauten Modul-Updater wie in [Sidebar-Webhook PR #22](https://github.com/fulldecent/freescout-sidebar-webhook/pull/22). Voraussetzung ist FreeScout ab 1.8.124 und ein für den FreeScout-Server öffentlich erreichbares GitHub-Release.

Der Workflow `.github/workflows/release.yml` erzeugt bei einem Versionstag `X.Y.Z` zwei Release-Assets:

- `version.txt`: ausschließlich die Versionsnummer, für Weboberfläche und CLI-Updater.
- `CustomerSystems.zip`: enthält genau den Modulordner `CustomerSystems/`, damit FreeScout die bestehende Installation aktualisiert.

Die Update-URLs in `module.json` zeigen auf [cfoellmann/freescout_CustomerSystems](https://github.com/cfoellmann/freescout_CustomerSystems). Der Build setzt diese Felder im Release-Paket zusätzlich anhand von `github.repository`, sodass auch ein bewusst in einem anderen Repository gebautes Paket dessen Releases verwendet. Das Quellmanifest bleibt beim Build unverändert.

Für ein Release:

1. Die Änderungen inklusive Workflow in `cfoellmann/freescout_CustomerSystems` übernehmen.
2. `version` in `Modules/CustomerSystems/module.json` erhöhen und committen.
3. Einen identischen Tag ohne `v` setzen und pushen, beispielsweise `git tag 0.1.1` und `git push origin 0.1.1`. Der Build bricht ab, falls Tag und Modulversion abweichen.
4. Der Workflow erstellt das Release mit beiden Assets. Ein vorhandenes Release kann über **Actions → Release → Run workflow** mit seinem Tag erneut bestückt werden.

Neue Releases werden mit Assets veröffentlicht; bei bestehenden Releases werden die Assets wie im Vorbild ersetzt. Für die Erstinstallation und eine bereits installierte Version 0.1.0 ohne Update-URLs einmal `CustomerSystems.zip` entpacken und `CustomerSystems/` nach `Modules/` kopieren. Anschließend erfolgen Updates über die FreeScout-Modulseite bzw. `php artisan freescout:module-update`. Es wird kein eigener Hintergrund-Updater installiert. Das Shopware-Begleitplugin ist separat zu aktualisieren und bleibt außerhalb des FreeScout-ZIPs.

Lokalen Release-Build mit dem tatsächlichen Repository prüfen:

```powershell
python -m unittest discover -s tests -p test_release.py -v
python tools/build_release.py --tag 0.1.1 --repository cfoellmann/freescout_CustomerSystems
```

Die Dateien liegen danach in `dist/`. Ein echtes Update einschließlich GitHub-Weiterleitungen und FreeScout-Installation muss nach dem ersten veröffentlichten Release geprüft werden. Private GitHub-Releases funktionieren mit diesen anonym abgerufenen URLs nicht.

## Kundenprofil direkt im Shopware-Backend öffnen

Jeder Treffer erhält den Link **Kundenprofil im Shopware-Backend öffnen**, sofern `CUSTOMER_SYSTEMS_SHOPWARE_BACKEND_URL` gesetzt ist. Der Link öffnet einen neuen Tab und verwendet die Shopware-Kunden-ID, nicht die Kundennummer.

Für das gezielte Öffnen der Detailansicht wird ein kleines Begleitplugin mitgeliefert. Hintergrund: Der Shopware-5.7-Kundencontroller benötigt `action: 'detail'`; die normale Backend-Start-URL reicht diese Eigenschaft nicht an die Kundenanwendung weiter.

1. Den Ordner `Shopware5/CustomerSystemsLink` nach `custom/plugins/CustomerSystemsLink` der Shopware-Installation kopieren.
2. Das Plugin im Shopware-Plugin-Manager installieren und aktivieren; anschließend den Backend-/Template-Cache leeren und das Backend neu laden.
3. Mit einer bestehenden Shopware-Backend-Sitzung den Link aus FreeScout öffnen. Beispiel: `https://shop.example.com/backend/?customerSystemsCustomerId=12`.

Das Begleitplugin lauscht auf den Backend-Start und öffnet das Kundenmodul mit `action: 'detail'` und `params.customerId`. Shopware-Anmeldung und Benutzerrechte gelten weiterhin. Nach einer erstmaligen Anmeldung den Link gegebenenfalls erneut öffnen. Ohne Begleitplugin öffnet sich lediglich das normale Backend.

## Prüfung und verbleibende Abnahme

Lokal ausführbar, ohne FreeScout oder echte Zugangsdaten:

```powershell
php tests/run.php
Get-ChildItem Modules,Shopware5,tests -Recurse -Filter *.php | Where-Object Name -NotLike '*.blade.php' | ForEach-Object { php -l $_.FullName }
node --check Modules/CustomerSystems/Public/js/sidebar.js
```

Die Tests verwenden simulierte API-Antworten für E-Mail-Zuordnung, Mehrfachtreffer, abweichende Kundendaten, Feldbegrenzung und Link-Erzeugung. Sie ersetzen keine Prüfung in der Zielinstallation. Die tatsächlichen FreeScout- und Shopware-Versionen wurden noch nicht angegeben; FreeScout-, Blade- und Shopware-Integration sind daher noch nicht live geprüft. Entwicklung orientiert sich an der FreeScout-Laravel-5.5-Modulstruktur und dem Shopware-5.7-Quellcode; PHP-Code verwendet Syntax ab PHP 7.1.

Vor Einsatz in der Zielinstallation prüfen:

- Bekanntes Ticket zeigt den richtigen Kunden und dessen Rechnungsanschrift.
- Kein Treffer, doppelte E-Mail und nicht erreichbares Shopware ergeben verständliche Meldungen.
- Benutzer ohne Zugriff auf das Ticket erhalten am Modulendpunkt HTTP 403.
- Der Backend-Link öffnet genau die gewählte Kundendetailansicht, auch aus einem Shop-Unterverzeichnis.
- Shopware-Rechte werden für einen eingeschränkten Backend-Benutzer eingehalten.

## Quellen

- [FreeScout-Modulentwicklung](https://github.com/freescout-help-desk/freescout/wiki/Modules-Development)
- [FreeScout-Sidebar-Hook](https://github.com/freescout-help-desk/freescout/blob/master/resources/views/conversations/view.blade.php)
- [Shopware-5-Kunden-API](https://github.com/shopware5/shopware/blob/5.7/engine/Shopware/Components/Api/Resource/Customer.php)
- [Shopware-Kundendetailaufruf](https://github.com/shopware5/shopware/blob/5.7/themes/Backend/ExtJs/backend/customer/controller/main.js)
- [Shopware-Backend-Startparameter](https://github.com/shopware5/shopware/blob/5.7/themes/Backend/ExtJs/backend/index/header.tpl)
