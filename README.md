# Kundenprofil Abruf für FreeScout

Shopware-6-Kundenprofile anhand der Kunden-E-Mail eines Tickets abrufen. Die Verbindung wird pro Postfach konfiguriert.

Der native Kundenbereich von FreeScout bleibt oben. Direkt vor der ersten Sidebar-Box erscheinen:

- Eine gemeinsame Box mit **Kundenname und Profil-Link im Kopf**, Kundennummer, Firma, E-Mail, Telefon und Rechnungsanschrift.
- Darin **Letzte Bestellungen**: maximal fünf Bestellungen dieses Kunden, neueste zuerst, mit Bestellnummer, Datum, Bestellstatus und Direktlink in den Shopware-Admin. Die Liste verwendet FreeScouts native Struktur von „Vorherige Gespräche“.
- Bei mehreren Konten derselben E-Mail erhält jeder Kunde seine eigene Bestellliste. Technische IDs werden nicht als Felder angezeigt.

Bestellungen erscheinen ab 0.3.2 jeweils in einer kompakten Zeile: Bestellnummer, Datum und natives farbiges Statuslabel. Offen ist blau, in Bearbeitung orange, abgeschlossen grün und storniert rot; eigene Status bleiben neutral grau. Lange Bezeichnungen und Nummern werden gekürzt, der vollständige Wert bleibt im Tooltip lesbar. Die Farbzuordnung verwendet den technischen Bestellstatus und nicht dessen übersetzten Namen.

Das Modul arbeitet lesend. Bestellungen laden getrennt nach; fehlerhafte Bestellabfragen verhindern nicht die Anzeige des Kundenprofils. büro+ ist ausgegraut als noch nicht verfügbar. Ab **0.3.0 wird ausschließlich Shopware 6 unterstützt**. Das frühere Begleitplugin und die konfigurierbare Sidebar-Überschrift entfallen.

Die Platzierung erfolgt ab 0.3.1 über den nativen Hook `conversation.before_prev_convs` nach dem Kundenprofil. Der Block wird nicht per JavaScript vor Elemente mit `conv-sidebar-block` verschoben: Diese Klasse trägt auch das native Kundenprofil, dessen Avatar einen negativen oberen Abstand verwendet. Dadurch bleibt die gemeinsame Box unterhalb des Profils und überlagert sich nicht mit dessen Bild.

## Erstinstallation – empfohlen mit GH-Installer

Wir empfehlen den [Github.com Installer für FreeScout](https://github.com/wus-technik/freescout_gh-installer) für die Erstinstallation.

Wenn der Installer noch nicht vorhanden ist:

1. `GHInstaller.zip` aus dessen [neuestem Release](https://github.com/wus-technik/freescout_gh-installer/releases/latest) herunterladen.
2. Den enthaltenen Ordner `GHInstaller/` nach `<freescout>/Modules/` kopieren.
3. PHP-Erweiterungen `curl`, `zip` und `json` sowie Schreibrechte des Webserver-Benutzers auf `Modules` und `storage/app` sicherstellen.
4. Unter **Verwalten → Module** den GHInstaller aktivieren.

Danach dieses Modul installieren:

1. Als FreeScout-Administrator **Verwalten → Einstellungen → Github.com Installer** öffnen.
2. Repository-URL: `https://github.com/cfoellmann/freescout_CustomerSystems`.
3. Den gewünschten veröffentlichten Release-Tag, z. B. `0.3.0`, separat als Referenz eintragen. Ohne Referenz wird der Standard-Branch verwendet.
4. **Repository prüfen** wählen. Die Vorschau muss „Kundenprofil Abruf“, Alias `customersystems` und technischen Ordner `CustomerSystems` erkennen.
5. Den geprüften Stand und das Vertrauen bestätigen, dann **Modul installieren**.
6. Unter **Verwalten → Module** „Kundenprofil Abruf“ aktivieren.
7. Shopware und das gewünschte FreeScout-Postfach wie unten konfigurieren.

Spätere Updates dieses Moduls erfolgen über **FreeScouts native Modulverwaltung**, weil es eigene Update-URLs besitzt. Der GH-Installer bietet dafür keinen zweiten Updateweg an.

### Alternative: manuell installieren

`CustomerSystems.zip` aus dem [neuesten Release](https://github.com/cfoellmann/freescout_CustomerSystems/releases/latest) herunterladen und den enthaltenen Ordner nach `<freescout>/Modules/CustomerSystems/` kopieren. Direkt darin muss `module.json` liegen. Unter **Verwalten → Module** aktivieren; bei Bedarf den FreeScout-Cache leeren.

Voraussetzungen: FreeScout ab 1.8.124, PHP-cURL, ausgehendes HTTPS zu Shop und GitHub sowie funktionierende CA-Zertifikate. Ein zusätzliches FreeScout-API-Modul ist nicht erforderlich.

## Shopware 6 genau konfigurieren

### 1. Rolle mit Leserechten anlegen

1. Als Administrator im **Shopware-6-Admin** anmelden.
2. **Einstellungen → System → Benutzer & Rechte** öffnen.
3. Im Bereich **Rollen** eine neue Rolle erstellen, z. B. **FreeScout Kunden und Bestellungen**.
4. Im Tab für detaillierte Berechtigungen bei diesen Einträgen ausschließlich **Lesen** aktivieren:

| Eintrag | Privileg | Zweck |
| --- | --- | --- |
| `customer` | `customer:read` | Kundenprofil und E-Mail-Zuordnung |
| `customer_address` | `customer_address:read` | Rechnungsanschrift und Telefon |
| `country` | `country:read` | Land der Anschrift |
| `order` | `order:read` | Bestellnummer und Bestelldatum |
| `order_customer` | `order_customer:read` | Zuordnung zum Kunden |
| `state_machine_state` | `state_machine_state:read` | Bestellstatus |

5. **Schreiben, Erstellen und Löschen deaktiviert lassen** und speichern.

„Status“ bezeichnet den Bestellstatus, nicht Zahlungs- oder Lieferstatus. Bestellpositionen, Zahlungsdaten und Lieferungen werden nicht abgefragt. Bereits im Tab „Allgemein“ gesetzte Rechte können in der Detailansicht ausgegraut sein.

### 2. Integration erstellen

1. **Einstellungen → System → Integrationen** öffnen.
2. **Integration anlegen** wählen.
3. Einen Namen wie **FreeScout Kundenprofil Abruf** vergeben.
4. Administratorberechtigung ausgeschaltet lassen und die oben erstellte Rolle zuweisen.
5. **Zugangs-ID / Access Key ID** und **Sicherheitsschlüssel / Secret Access Key** sicher übernehmen.
6. Die Integration speichern.

Der Sicherheitsschlüssel wird in Shopware nur bei Erstellung bzw. Neuerzeugung angezeigt. Ist er nicht mehr verfügbar, **API-Zugangsschlüssel neu generieren** wählen. Dabei ändern sich beide Werte; anschließend beide in FreeScout ersetzen.

Verwendet werden die Schlüssel dieser Integration. Backend-Benutzername, Benutzerpasswort, Verkaufskanal-API-Key und ein kurzlebiger OAuth-Token gehören nicht in die Eingabefelder. Den OAuth-Token fordert das Modul selbst an.

### 3. FreeScout-Postfach einrichten

1. Als FreeScout-Administrator **Verwalten → Postfächer → gewünschtes Postfach → Kundenprofil Abruf** öffnen.
2. Folgende Werte eintragen:

| FreeScout-Feld | Wert |
| --- | --- |
| API-URL | `https://dein-shop.de/api` |
| Backend-URL | `https://dein-shop.de/admin` |
| Access-Key-ID | Zugangs-ID der Shopware-Integration |
| Secret Access Key | Sicherheitsschlüssel derselben Integration |

3. Bei Installation in einem Unterverzeichnis dieses in beiden URLs beibehalten.
4. **Verbindung testen** wählen. Der Test prüft Anmeldung und Lesezugriff auf Kunden, Bestellungen und Bestellstatus, speichert aber noch nichts.
5. **Für dieses Postfach aktivieren** anhaken und **Speichern**.
6. Ein Ticket eines bekannten Shopkunden öffnen und Profil sowie Bestellungen prüfen.

URLs müssen HTTPS verwenden und dürfen keine Zugangsdaten, Query-Parameter oder Fragmente enthalten. Die Backend-URL ist für Direktlinks erforderlich; ohne sie erscheinen Bestellungen als Text.

Ein leeres Secret-Feld behält einen bereits gespeicherten Schlüssel bei. Bei geänderter API-URL oder Access-Key-ID das Secret neu eingeben. Zum Entfernen Verbindung deaktivieren und **Gespeicherten Schlüssel löschen** wählen.

### Bestehende Installation aktualisieren

Gespeicherte Shopware-6-Zugangsdaten pro Postfach bleiben erhalten. Für die Bestellanzeige `order:read`, `order_customer:read` und `state_machine_state:read` zur Rolle ergänzen und den Verbindungstest erneut ausführen. Die frühere Überschriften-Einstellung entfällt; im Boxkopf steht der Kundenname.

## Zuordnung und Sicherheit

Die Bestellabfrage filtert gleichzeitig nach Kunden-ID und der aktuellen Kunden-E-Mail des berechtigten Tickets und prüft die Zuordnung nochmals in der Antwort. Eine geänderte Kunden-ID im Modulendpunkt darf keine Bestellungen eines fremden Kunden liefern. Profil- und Bestellendpunkt prüfen die bestehende Ticketberechtigung.

Die Verbindungskonfiguration liegt verschlüsselt in den Postfach-Metadaten. Dafür wird FreeScouts `APP_KEY` verwendet; diesen mit den Datenbanksicherungen sicher aufbewahren. Nur FreeScout-Administratoren dürfen konfigurieren und testen. Schlüssel werden nicht im Browser oder in Session-Alteingaben ausgegeben.

Direktlinks öffnen die Shopware-Administration in einem neuen Tab. Dort gelten Anmeldung und Rechte des jeweiligen Shopware-Benutzers. Die Integration meldet Supportmitarbeiter nicht automatisch im Admin an.

## Diagnose-Logging

Dateien: `storage/logs/customer-systems-YYYY-MM-DD.log`, maximal 14 Tagesdateien. Rotation beim Schreiben neuer Dateien; der PHP-Benutzer benötigt Schreibrechte auf `storage/logs`.

Fehler, Verbindungstests und Konfigurationsänderungen werden immer protokolliert. **Diagnose-Logging** im Postfach ergänzt erfolgreiche Profil- und Bestellabrufe samt Trefferanzahl. Einträge enthalten Referenz, Postfach-/Ticket-ID, Laufzeit und technische Fehlercodes; unerwartete PHP-Fehler zusätzlich Typ, PHP-Version sowie Moduldateiname und Zeile.

Keine E-Mail-Adressen, Kunden- oder Bestellinhalte, URLs, Schlüssel, Tokens oder rohen Exception-Texte im Modul-Log. Fehlgeschlagene Verbindungstests zeigen eine passende Log-Referenz an.

```sh
tail -f storage/logs/customer-systems-$(date +%F).log
```

| Fehler | Prüfung |
| --- | --- |
| HTTP 401 bei OAuth | Access-Key-ID und Secret derselben Integration neu eintragen |
| HTTP 403 | Die sechs Leserechte und ihre Zuweisung an die Integration prüfen |
| HTTP 3xx | Endgültige HTTPS-API-URL verwenden |
| cURL 6 / 7 | DNS bzw. Netzwerk vom FreeScout-Server prüfen |
| cURL 28 | Zeitüberschreitung |
| cURL 60 | CA-Zertifikate und Zertifikatskette auf dem FreeScout-Server prüfen |
| `credentials_unreadable` | FreeScout-`APP_KEY` prüfen; Verbindung gegebenenfalls neu speichern |
| Interner PHP-Fehler | Angezeigte Datei, Zeile und Referenz prüfen; aktuelle Version verwenden |

## Updates und Releases

Der native FreeScout-Updater nutzt wie in [Sidebar-Webhook PR #22](https://github.com/fulldecent/freescout-sidebar-webhook/pull/22) öffentlich erreichbare Release-Dateien: `version.txt` mit der Versionsnummer und `CustomerSystems.zip` mit Wurzelordner `CustomerSystems/`.

Updates unter **Verwalten → Module** oder mit `php artisan freescout:module-update`. Private GitHub-Releases werden nicht unterstützt.

Für ein Release die Version in `Modules/CustomerSystems/module.json` erhöhen, committen und einen identischen Tag ohne `v` pushen. Der Workflow prüft Version und Tests, baut beide Assets und veröffentlicht sie. Bestehende Tags können über **Actions → Release → Run workflow** erneut gebaut werden.

```powershell
python -m unittest discover -s tests -p test_release.py -v
python tools/build_release.py --tag 0.3.2 --repository cfoellmann/freescout_CustomerSystems
```

## Entwicklung und Prüfung

```powershell
php tests/logging.php
php tests/orders.php
php tests/sidebar.php
php tests/php_compatibility.php
Get-ChildItem Modules,tests -Recurse -Filter *.php | Where-Object Name -NotLike '*.blade.php' | ForEach-Object { php -l $_.FullName }
node --check Modules/CustomerSystems/Public/js/sidebar.js
node --check Modules/CustomerSystems/Public/js/settings.js
```

Die Tests verwenden simulierte API-Antworten und Framework-Adapter für Zuordnung, Postfachtrennung, Berechtigungen, OAuth, Bestellungen, Links und Datenfilterung. Darstellung und Rechte sind zusätzlich in der Zielinstallation zu prüfen. PHP 8.5 wird mit strenger Fehlerbehandlung geprüft.

## Quellen

- [Shopware-6-Integrationen](https://docs.shopware.com/de/shopware-6-de/einstellungen/system/integrationen)
- [Shopware Benutzer & Rechte](https://docs.shopware.com/de/shopware-6-de/einstellungen/System/benutzer)
- [Shopware Admin API und OAuth](https://developer.shopware.com/docs/guides/development/integrations-api/)
- [Shopware Suchkriterien](https://developer.shopware.com/docs/guides/development/integrations-api/search-criteria.html)
- [FreeScout-Modulentwicklung](https://github.com/freescout-help-desk/freescout/wiki/Modules-Development)
- [Native Liste „Vorherige Gespräche“](https://github.com/freescout-help-desk/freescout/blob/master/resources/views/conversations/partials/prev_convs_short.blade.php)
