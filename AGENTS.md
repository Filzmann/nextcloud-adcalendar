# AGENTS.md - AD Kalender

## Projekt

Nextcloud-App `adcalendar` fuer die uebersichtliche Dienstplanung der Mitarbeiter*innen im Buero.

Lokale App-URL:

    https://nextcloud-dev.ddev.site/apps/adcalendar/

Nextcloud-App-ID:

    adcalendar

## Zielsetzung und Fachkontext

AD Kalender uebertraegt den fachlichen Kern des bisherigen WordPress-Plugins `ad_calendar` in eine eigenstaendige Nextcloud-App. WordPress-Code, Rollen, Nonces, Shortcodes und die Abhaengigkeit `flz-wpdb-objects` werden nicht uebernommen.

Kernprozess:

- Eine Wochenansicht zeigt Mitarbeiter*innen als Zeilen und Kalendertage als Spalten.
- Dienste besitzen Mitarbeiter*in, Beginn und Ende. Der Titel bleibt bei Diensten optional.
- Termine besitzen zusaetzlich einen sprechenden Titel.
- Termine innerhalb eines Dienstes werden diesem Dienst in der Darstellung zugeordnet.
- Termine ausserhalb eines Dienstes gelten fachlich als Sperrtermine und werden deutlich, nicht nur farblich, gekennzeichnet.
- Pro Mitarbeiter*in werden Dienstanzahl und gesamte Dienstzeit fuer den sichtbaren Zeitraum ausgewiesen.
- Filter nach Mitarbeiter*innen und Nextcloud-Gruppen sollen die Ansicht begrenzen.
- Zeitwerte werden serverseitig eindeutig gespeichert und fuer die Anzeige in der konfigurierten Nextcloud-Zeitzone formatiert.

Verbindliches Gruppenschema:

- Rollen werden als eigenstaendige Nextcloud-Gruppen gepflegt: `ad-EB` und `ad-PFK`.
- Bereiche werden separat als `ad-Bereich-<Name>` gepflegt.
- Nur `ad-Buero` und `ad-EB` werden einem Buero-Bereich zugeordnet. `ad-PFK`, `ad-Stab-HR` und `ad-Stab-QMB` sind bereichsunabhaengig; versehentliche Bereichsmitgliedschaften duerfen ihre Kalenderdarstellung oder Rechte nicht veraendern.
- Kombinierte Gruppen sind abgeleitete Schnittmengen, zum Beispiel Mitgliedschaft in `ad-EB` und `ad-Bereich-Nordost`; es werden keine Kombinationsgruppen dupliziert.
- `ad-EB` und `ad-PFK` sind Zielrollen im Kalender; die EB-Rolle allein verleiht keine globale Fremdbearbeitung.
- Alle angemeldeten Nutzer*innen duerfen alle Kalenderdaten lesen; alle duerfen eigene Eintraege bearbeiten.
- `ad-PDL` darf Eintraege von `ad-PFK` bearbeiten.
- `ad-BL-Sued` und `ad-StvBL-Sued` duerfen `ad-Buero` und `ad-EB` im Bereich Sued bearbeiten.
- `ad-BL-Nordost-West` darf `ad-Buero` und `ad-EB` in Nordost und West bearbeiten.
- `ad-StvBL-Nordost` und `ad-StvBL-West` duerfen den jeweiligen Bereich bearbeiten.
- `ad-Stab-HR` und `ad-Stab-QMB` sind sichtbare Stabsstellen; gegenseitige Bearbeitung innerhalb der jeweiligen Gruppe wird ueber den Peer-Schalter gesteuert.
- Peer-Bearbeitung kann in den App-Einstellungen getrennt fuer `ad-Buero`, `ad-PFK`, `ad-EB`, `ad-Stab-HR` und `ad-Stab-QMB` aktiviert werden. Sie ist standardmaessig aus. Bei BO und EB gilt sie nur innerhalb mindestens eines gemeinsamen Buerobereichs; PFK und Stabsstellen bleiben mangels Buerobereich innerhalb ihrer Fachgruppe berechtigt.
- Assistent*innen erscheinen nicht in dieser App; ihre Planung bleibt im AdPlaner.
- Nextcloud-Admins duerfen alle Eintraege verwalten.

Offene Fachentscheidungen:
- Dienste derselben Person duerfen sich nicht ueberschneiden; dadurch bleibt die Terminzuordnung eindeutig.
- Welche Auswertungszeitraeume neben der Woche benoetigt werden.
- Ob und wie Bestandsdaten aus WordPress importiert werden.

## Rechte- und Zugriffsschutz

Es gilt deny by default fuer schreibende Zugriffe:

- Sehen: alle angemeldeten Nutzer*innen.
- Eigene Eintraege anlegen/aendern/loeschen: alle angemeldeten Nutzer*innen.
- Fremde Eintraege anlegen/aendern/loeschen: nur mit expliziter Planungsberechtigung.
- Mitarbeiter*innen und Gruppenzuordnung verwalten: nur App-Administration.
- Jeder API-Endpunkt prueft die Berechtigung serverseitig ueber einen zentralen `CalendarAccessService`.
- UI-Ausblendungen sind Komfort und niemals die einzige Zugriffskontrolle.
- Listen werden bereits serverseitig auf den erlaubten Personenkreis eingeschraenkt.
- Allow- und Deny-Faelle sowie direkte unberechtigte API-Aufrufe werden getestet.

Die Gruppenlogik wird zentral implementiert und serverseitig erzwungen.

## Architektur

- Controller bleiben duenn.
- `CalendarAccessService` buendelt Sicht-, Erstell-, Aenderungs- und Loeschrechte.
- Repository-/Store-Klassen kapseln Datenzugriffe und QueryBuilder-Parameter.
- Fachregeln fuer Zeitraeume, Zuordnung und Summen liegen in Services und Value Objects.
- Persistente Kernobjekte nutzen `get(...)`, `get_all([...])`, `toArray()` und nur bei Store-Bindung `save()`.
- Dienste und Termine werden als ein gemeinsamer Kalendereintrag mit explizitem Typ modelliert; die fachliche Darstellung eines externen Termins als Sperrtermin wird abgeleitet und nicht als widerspruechliche zweite Datenwahrheit gespeichert.
- Termine innerhalb eines Dienstes referenzieren diesen explizit ueber `parent_entry_id`; Termine ohne Parent sind Sperrtermine.
- Bestehende Eintraege duerfen ihren Typ nicht wechseln; Dienst und Termin haben unterschiedliche Folge- und Loeschvertraege.
- Beim Loeschen eines Dienstes muss zwischen gemeinsamem Loeschen der Termine und deren Erhalt als Sperrtermine gewaehlt werden.
- API-Zugriffe liegen im Frontend in Repositories, Daten in Modellen/ViewModels und Rendering/Eventbindung in Komponenten.
- Die UI bleibt per Tastatur bedienbar, verwendet semantische Tabellen/Listen, sichtbare Fokuszustaende und Textkennzeichnungen zusaetzlich zu Farben.
- Keine vorsorgliche gemeinsame Library und keine WordPress-Kompatibilitaetsschicht.

## Git- und Arbeitsregeln

- Dieses Verzeichnis ist ein eigenstaendiges Git-Repository.
- Keine Commits, kein Push und kein Deployment ohne ausdrueckliche Freigabe durch Simon.
- Vor Commits `git status --short`, `git diff --stat` und `git diff --name-only` zeigen.
- Dateien gezielt stagen; nie `git add .`.
- Aenderungen klein, pruefbar und rueckbaubar halten.
- Die Parent-`AGENTS.md` gilt ergaenzend.

## DDEV

Die gemeinsame Umgebung liegt unter:

    ~/projects/br-nextcloud-apps/nextcloud-dev

Geplante Checks:

    ddev exec -d /var/www/html/html php occ status
    ddev exec -d /var/www/html/html php occ app:list | grep -i adcalendar

Migrationen laufen beim Aktivieren der App bzw. ueber `occ upgrade`; `occ migrations:migrate` steht in der lokalen Nextcloud-34-Umgebung nicht zur Verfuegung. Zustandsaendernde DDEV-/`occ`-Befehle brauchen ausdrueckliche Freigabe.

## Tests

- Schnelle PHP-Suite: `php tests/run.php`
- Schnelle JavaScript-Suite: `node tests/run-js.mjs`
- Fachregeln erhalten Unit-/Charakterisierungstests vor Repository- und Controller-Anbindung.
- Berechtigungstests decken mindestens Lesen, eigene Bearbeitung, Fremdbearbeitung und direkte Deny-Aufrufe ab.
- Bei Migration, Controller, DI oder Nextcloud-Container zusaetzlich gezielte DDEV-/`occ`-Checks.
- Testdaten bleiben kuenstlich, neutral und datenschutzarm.
- Authentifizierter App-/API-Smoke: `ADC_BASE_URL=... ADC_USER=... ADC_PASSWORD=... tests/http-smoke.sh`
- Serverseitiger Rechte-Smoke: `ADC_BASE_URL=... ADC_USER=... ADC_PASSWORD=... ADC_EXPECTED='uid=true,...' tests/access-http-smoke.sh`

## Learnings

- Wiederverwendbare app-spezifische Learnings werden Simon zuerst im Standardformat vorgeschlagen.
- Dauerhafte Ergaenzungen dieser Datei erfolgen nur nach ausdruecklicher Freigabe.
- App-uebergreifende Regeln gehoeren in die Parent-`AGENTS.md`.
