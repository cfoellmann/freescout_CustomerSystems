# FreeScout Kundenprofil Abruf

FreeScout-Modul für **Shopware 5 und Shopware 6**. Die Zuordnung erfolgt über die `customer_email` des geöffneten Tickets. Die Verbindung lässt sich pro Postfach konfigurieren. **büro+ wird als ausgegraute, noch nicht verfügbare Verbindung angezeigt.** Der technische Modulordner bleibt `CustomerSystems` und der Alias `customersystems`.

Angezeigt werden Kundennummer, Shopware-ID, Shop-ID, Firma, Name, E-Mail, Telefon und Rechnungsanschrift. Die Kundendaten laden nach dem Ticket asynchron. Mehrere Treffer werden einzeln angezeigt; bei mehr als fünf Treffern fordert das Modul zur Prüfung im Shop auf. Es verändert keine Daten und speichert keine Shopware-Kundendaten in FreeScout.

## Installation in FreeScout

1. `Modules/CustomerSystems` in das gleichnamige `Modules`-Verzeichnis der FreeScout-Installation kopieren. Der Ordner muss `CustomerSystems` heißen.
2. In FreeScout **Verwalten → Module** das Modul aktivieren und den FreeScout-Cache über die Systemwerkzeuge leeren. HTTPS und PHP-cURL sind erforderlich.
3. Als Administrator unter **Verwalten → Postfächer → gewünschtes Postfach → Kundenprofil Abruf** die Verbindung einrichten.
4. System auswählen, Zugangsdaten eingeben und **Verbindung testen**. Anschließend die Verbindung aktivieren und **Speichern**.
5. Für Shopware-5-Backend-Direktlinks zusätzlich das unten beschriebene Begleitplugin installieren. Shopware 6 benötigt kein Begleitplugin.

## Verbindung pro Postfach

Die Einstellungsseite ist ausschließlich für FreeScout-Administratoren zugänglich. Auch Speichern und Verbindungstest prüfen die Administratorrolle. Konfigurierbar sind Aktivierung, System (Shopware 5 oder 6), API-URL, optionale Backend-URL, Benutzer / Access-Key-ID, API-Key / Secret und bei Shopware 5 Digest oder Basic.

| Einstellung | Shopware 5 | Shopware 6 |
| --- | --- | --- |
| API-URL | `https://shop.example.com/api` | `https://shop.example.com/api` |
| Backend-URL | `https://shop.example.com/backend` | `https://shop.example.com/admin` |
| Benutzerfeld | API-Benutzer | Access-Key-ID einer Integration |
| Schlüsselfeld | API-Key | Secret Access Key |
| Anmeldung | Digest oder Basic ab 5.3.2 | OAuth 2.0 Client Credentials |

Für Shopware 6 unter **Einstellungen → System → Integrationen** einen Zugang mit Leserechten für Kunden und zugehörige Adressen, Länder und Verkaufskanäle einrichten. Die Kundensuche verwendet die Admin API mit exaktem E-Mail-Filter und lädt die Standard-Rechnungsadresse. Shopware 5 verwendet die Kunden-REST-API mit E-Mail-Filter und anschließender Detailabfrage. Mehrere Konten derselben E-Mail werden separat dargestellt.

**Verbindung testen** prüft die aktuellen Formulareingaben, die Anmeldung und einen minimalen Lesezugriff auf die Kundenliste. Der Test funktioniert vor der Aktivierung und speichert keine Änderungen. Er bestätigt nicht, ob für eine bestimmte Ticket-E-Mail ein Kunde existiert.

Die gesamte Verbindungskonfiguration wird verschlüsselt in den Metadaten des jeweiligen Postfachs gespeichert; dazu wird FreeScouts `APP_KEY` verwendet. Den Schlüssel mit den Datenbanksicherungen sicher aufbewahren. Das Schlüsselfeld bleibt nach dem Speichern leer. Eine leere Eingabe behält den vorhandenen Schlüssel bei. Werden System, API-Ziel, Benutzer oder Authentifizierung geändert, muss der Schlüssel neu eingegeben werden. Die Checkbox zum Löschen entfernt ihn bei deaktivierter Verbindung. Schlüssel werden auch bei Validierungsfehlern nicht in Formular oder Session-Alteingaben zurückgegeben.

Für bestehende Installationen gelten die bisherigen `.env`-Werte aus `.env.example` als Shopware-5-Fallback, solange ein Postfach noch keine eigene Konfiguration hat. Beim Speichern wird die Verbindung in die verschlüsselten Postfach-Einstellungen übernommen. Eine ausdrücklich deaktivierte Postfach-Verbindung überschreibt den Fallback. Neue Installationen benötigen keine `.env`-Konfiguration für dieses Modul.

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
python tools/build_release.py --tag 0.2.0 --repository cfoellmann/freescout_CustomerSystems
```

Die Dateien liegen danach in `dist/`. Ein echtes Update einschließlich GitHub-Weiterleitungen und FreeScout-Installation muss nach dem ersten veröffentlichten Release geprüft werden. Private GitHub-Releases funktionieren mit diesen anonym abgerufenen URLs nicht.

## Kundenprofil direkt im Shopware-Backend öffnen

Jeder Treffer erhält den Link **Kundenprofil im Shopware-Backend öffnen**, sofern eine Backend-URL im Postfach konfiguriert ist. Der Link öffnet einen neuen Tab und verwendet die Shopware-Kunden-ID, nicht die Kundennummer.

Bei Shopware 6 öffnet der Link die Administration unter `#/sw/customer/detail/<UUID>/base`. Anmeldung und Shopware-Benutzerrechte gelten weiterhin. Der folgende Abschnitt betrifft ausschließlich Shopware 5.

Für das gezielte Öffnen der Detailansicht wird ein kleines Begleitplugin mitgeliefert. Hintergrund: Der Shopware-5.7-Kundencontroller benötigt `action: 'detail'`; die normale Backend-Start-URL reicht diese Eigenschaft nicht an die Kundenanwendung weiter.

1. Den Ordner `Shopware5/CustomerSystemsLink` nach `custom/plugins/CustomerSystemsLink` der Shopware-Installation kopieren.
2. Das Plugin im Shopware-Plugin-Manager installieren und aktivieren; anschließend den Backend-/Template-Cache leeren und das Backend neu laden.
3. Mit einer bestehenden Shopware-Backend-Sitzung den Link aus FreeScout öffnen. Beispiel: `https://shop.example.com/backend/?customerSystemsCustomerId=12`.

Das Begleitplugin lauscht auf den Backend-Start und öffnet das Kundenmodul mit `action: 'detail'` und `params.customerId`. Shopware-Anmeldung und Benutzerrechte gelten weiterhin. Nach einer erstmaligen Anmeldung den Link gegebenenfalls erneut öffnen. Ohne Begleitplugin öffnet sich lediglich das normale Backend.

## Prüfung und verbleibende Abnahme

Lokal ausführbar, ohne FreeScout oder echte Zugangsdaten:

```powershell
php tests/controllers.php
Get-ChildItem Modules,Shopware5,tests -Recurse -Filter *.php | Where-Object Name -NotLike '*.blade.php' | ForEach-Object { php -l $_.FullName }
node --check Modules/CustomerSystems/Public/js/sidebar.js
node --check Modules/CustomerSystems/Public/js/settings.js
```

Die Tests verwenden simulierte API-Antworten für beide Shopware-Systeme, E-Mail-Zuordnung, Mehrfachtreffer, abweichende Kundendaten, Feldbegrenzung, OAuth und Link-Erzeugung. Postfachtrennung, Credential-Wechsel und Controller-Berechtigungen werden mit Framework-Adaptern geprüft; diese ersetzen keinen Integrationstest gegen FreeScouts tatsächliche Datenbank und Verschlüsselung. Die tatsächlichen FreeScout- und Shopware-Versionen wurden noch nicht angegeben; die Zielinstallation ist daher noch nicht live geprüft. Entwicklung orientiert sich an der FreeScout-Laravel-5.5-Modulstruktur sowie Shopware 5.7 und der Shopware-6-Admin-API; PHP-Code verwendet Syntax ab PHP 7.1.

Vor Einsatz in der Zielinstallation prüfen:

- Bekanntes Ticket zeigt den richtigen Kunden und dessen Rechnungsanschrift.
- Kein Treffer, doppelte E-Mail und nicht erreichbares Shopware ergeben verständliche Meldungen.
- Benutzer ohne Zugriff auf das Ticket erhalten am Modulendpunkt HTTP 403.
- Der Backend-Link öffnet genau die gewählte Kundendetailansicht, auch aus einem Shop-Unterverzeichnis.
- Shopware-Rechte werden für einen eingeschränkten Backend-Benutzer eingehalten.
- Zwei Postfächer verwenden unabhängig voneinander unterschiedliche Verbindungen; Deaktivieren unterbindet API-Abfragen.
- Einstellungen, Speicherung und Verbindungstest sind für Nicht-Administratoren gesperrt.

## Quellen

- [FreeScout-Modulentwicklung](https://github.com/freescout-help-desk/freescout/wiki/Modules-Development)
- [FreeScout-Sidebar-Hook](https://github.com/freescout-help-desk/freescout/blob/master/resources/views/conversations/view.blade.php)
- [Shopware-5-Kunden-API](https://github.com/shopware5/shopware/blob/5.7/engine/Shopware/Components/Api/Resource/Customer.php)
- [Shopware-Kundendetailaufruf](https://github.com/shopware5/shopware/blob/5.7/themes/Backend/ExtJs/backend/customer/controller/main.js)
- [Shopware-Backend-Startparameter](https://github.com/shopware5/shopware/blob/5.7/themes/Backend/ExtJs/backend/index/header.tpl)
- [Shopware-6-Admin-API und OAuth](https://developer.shopware.com/docs/guides/development/integrations-api/)
- [Shopware-6-Suchkriterien](https://developer.shopware.com/docs/guides/development/integrations-api/search-criteria.html)
- [Shopware-6-Integrationen](https://docs.shopware.com/de/shopware-6-de/einstellungen/system/integrationen)
