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
- Ohne bewusst gespeicherten Standard startet die Ansicht mit den Fachrollen und Bereichen des eingeloggten Kontos. Die aktuelle Filter-/Personen-/Ansichtskonfiguration wird nur ueber „Zum Standard machen“ als persoenlicher Nextcloud-Benutzerwert gespeichert.
- `ad-Stab-HR`, `ad-Stab-QMB`, `ad-AsdGF-Digi`, `ad-PDL`, beide GF-Rollen und `ad-Sekretariat` werden im Rollenfilter ueber einen gemeinsamen Anzeigen-/Ausblenden-Schalter gesteuert; der Schalter ist Teil des persoenlichen Standards. Diese Personen stehen in einem gemeinsamen Block und werden entlang der GF-AS- und GF-Digi-Hierarchie sortiert.
- Eine Meeting-Lückensuche schneidet die Dienste der ausgewählten Personen innerhalb einer Kalenderwoche und zieht deren vorhandene Termine ab; die Mindestdauer ist frei wählbar und beträgt initial 60 Minuten. Bleibt eine Woche ohne Treffer, kann wochenweise weitergesucht oder eine Person direkt abgewählt werden.
- Gefundene Slots können mit einem gemeinsamen Titel atomar für alle ausgewählten Personen blockiert werden, wenn der angemeldete Nutzer jeden Zielkalender nach dem normalen Hierarchie-/Peer-Vertrag bearbeiten darf. Die Funktion erweitert keine Fremdbearbeitungsrechte und prüft die Verfügbarkeit unmittelbar vor dem Speichern erneut.
- Die dabei je Person gespeicherten Termine tragen eine gemeinsame Meeting-Kennung. Zeitraum und Titel werden für alle Beteiligten gemeinsam geändert, und das Meeting wird nur vollständig gelöscht. Beide Aktionen sind nur erlaubt, wenn der angemeldete Nutzer weiterhin jeden beteiligten Kalender bearbeiten darf; isolierte Änderungen über die allgemeine Eintrags-API werden abgewiesen.
- Jedes angemeldete Konto kann im eigenen Einstellungs-Tab Standard-Dienstzeiten je Wochentag speichern. Sie dienen als Vorschlag beim Anlegen; ein Ende vor dem Beginn bildet einen Dienst bis zum Folgetag.
- Bewusst gespeicherte Standard-Dienstzeiten werden beim Aufruf einer Woche als normale Dienste materialisiert. Individuell bearbeitete Vorkommen bleiben einmalige Abweichungen; ein geloeschtes Vorkommen bleibt fuer genau dieses Datum dauerhaft unterdrueckt.
- Zeitwerte werden serverseitig eindeutig gespeichert und fuer die Anzeige in der konfigurierten Nextcloud-Zeitzone formatiert.
- `adcalendar:demo:seed` synchronisiert neutrale, benannte Demokonten fuer jede Kalenderrolle und jeden Buerobereich. Namen tragen die fachliche Demo-Zuordnung in Klammern; Mehrfachrollen werden auch in Gruppentiteln als Hauptrolle mit weiteren Rollen in Klammern dargestellt.
- Ist `adurlaub` aktiviert, erscheinen geplante Urlaube read-only als `U?` ohne Blockade. Genehmigte Urlaube erscheinen als `U`, blockieren neue Dienste/Termine, verhindern Standarddienst-Materialisierung und werden aus Meetingluecken entfernt. Genehmigungen mit bestehenden Eintraegen werden ueber einen read-only Konfliktvertrag bereits in `adurlaub` abgelehnt.

Verbindliches Gruppenschema:

Die folgenden Gruppen-IDs beschreiben ausschließlich die initiale Standardkonfiguration. Gruppen-IDs, sichtbare Namen, Bereiche, Reihenfolge, Peer-Fähigkeit, Assistenzteam-Konventionen und direkte Hierarchiekanten werden gemeinsam über `AdOrganizationDefinition` konfiguriert und im Nextcloud-Adminbereich der OrgSuite bearbeitet. App-Code darf daneben keine parallelen Rollenregister führen.

- Rollen werden als eigenstaendige Nextcloud-Gruppen gepflegt: `ad-EB` und `ad-PFK`.
- Bereiche werden separat als `ad-Bereich-<Name>` gepflegt.
- Nur `ad-Buero` und `ad-EB` werden einem Buero-Bereich zugeordnet. `ad-PFK`, `ad-Stab-HR` und `ad-Stab-QMB` sind bereichsunabhaengig; versehentliche Bereichsmitgliedschaften duerfen ihre Kalenderdarstellung oder Rechte nicht veraendern.
- Kombinierte Gruppen sind abgeleitete Schnittmengen, zum Beispiel Mitgliedschaft in `ad-EB` und `ad-Bereich-Nordost`; es werden keine Kombinationsgruppen dupliziert.
- `ad-EB` und `ad-PFK` sind Zielrollen im Kalender; die EB-Rolle allein verleiht keine globale Fremdbearbeitung.
- `ad-EB` und `ad-Bereich-*` sind derselbe kanonische Rollen-/Bereichsvertrag wie im AdPlaner; kombinierte Altgruppen wie `ad-EB-*` werden nicht als Rollenquelle verwendet.
- Alle angemeldeten Nutzer*innen duerfen alle Kalenderdaten lesen; alle duerfen eigene Eintraege bearbeiten.
- Alle angemeldeten Nutzer*innen duerfen aus den ohnehin sichtbaren Kalenderdaten gemeinsame Meetingluecken berechnen.
- Das gemeinsame Blockieren nutzt ausschließlich bestehende Bearbeitungsrechte für jede einzelne ausgewählte Person; fehlt eines davon, wird kein Teil des Meetings gespeichert.
- `ad-PDL` darf Eintraege von `ad-PFK` bearbeiten.
- Bueroleitungen werden wie BO dynamisch aus `ad-BL` plus `ad-Bereich-*` gebildet. BL NOW ist Mitglied in `ad-BL`, `ad-Bereich-Nordost` und `ad-Bereich-West` und wird dadurch in BL-NO sowie BL-W gefunden.
- Stellvertretungen werden aus `ad-StvBL` plus genau ihrem `ad-Bereich-*` gebildet; ihre zusaetzliche Hauptberufsrolle `ad-EB` bleibt davon getrennt.
- `ad-Stab-HR` und `ad-Stab-QMB` sind sichtbare Stabsstellen; gegenseitige Bearbeitung innerhalb der jeweiligen Gruppe wird ueber den Peer-Schalter gesteuert.
- Peer-Bearbeitung kann im Nextcloud-Adminbereich der OrgSuite getrennt fuer die peer-fähigen Fachrollen aktiviert werden. Sie ist standardmaessig aus. Bei BO und EB gilt sie nur innerhalb mindestens eines gemeinsamen Buerobereichs; PFK und Stabsstellen bleiben mangels Buerobereich innerhalb ihrer Fachgruppe berechtigt.
- Assistent*innen erscheinen nicht in dieser App; ihre Planung bleibt im AdPlaner.
- Nextcloud-Admins duerfen alle Eintraege verwalten.

Hierarchie fuer Fremdbearbeitung:

- `ad-GF-AS` fuehrt PDL, beide Bueroleitungen, deren Stellvertretungen, PFK, BO, EB, HR, QMB und das Sekretariat direkt oder indirekt.
- `ad-GF-Digi` fuehrt `ad-AsdGF-Digi`, `ad-Leitung-Finanzen-Lohn`, `ad-Finanzen-Lohn`, IT und das Sekretariat direkt oder indirekt.
- `ad-AsdGF-Digi` fuehrt `ad-IT`; `ad-Leitung-Finanzen-Lohn` fuehrt `ad-Finanzen-Lohn`.
- `ad-PDL` fuehrt `ad-PFK`.
- Bueroleitungen und ihre bereichsbezogenen Stellvertretungen fuehren ausschliesslich BO und EB im passenden Buerobereich, nicht PFK.
- `ad-Sekretariat` ist beiden Geschaeftsfuehrungen direkt zugeordnet.
- Leitungsrollen schuetzen immer vor Peer-Bearbeitung durch unterstellte Hauptberufsgruppen. Eine StvBL kann zum Beispiel zugleich EB sein, darf aber niemals durch normale EBs bearbeitet werden.
- Vorgesetzte duerfen Kalenderdaten aller direkt und indirekt unterstellten Personen bearbeiten; Untergebene duerfen keine uebergeordneten Rollen bearbeiten.

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
- Der Einstellungs-Tab ist fuer alle sichtbar und enthält nur persönliche Einstellungen des eingeloggten Kontos. Organisationsweite Gruppenbearbeitungsrechte werden ausschließlich im Nextcloud-Adminbereich der OrgSuite gelesen und geändert.
- Listen werden bereits serverseitig auf den erlaubten Personenkreis eingeschraenkt.
- Allow- und Deny-Faelle sowie direkte unberechtigte API-Aufrufe werden getestet.

Die Gruppenlogik wird zentral implementiert und serverseitig erzwungen.

Eine Änderung technischer Gruppen-IDs verschiebt keine bestehenden Nextcloud-Gruppenmitgliedschaften. Zielgruppen und Mitgliedschaften müssen vor einer Umstellung in Nextcloud vorbereitet werden.

Urlaubsansichten sind dynamisch ergänzbare Rollen-/Bereichsschnitte. Die Standardansichten Büro Nordost, Büro West und Büro Süd bleiben getrennt; eine bereichsübergreifende Büroleitung erscheint aufgrund ihrer Mitgliedschaften in allen passenden Ansichten.

## Architektur

- Controller bleiben duenn.
- `CalendarAccessService` buendelt Sicht-, Erstell-, Aenderungs- und Loeschrechte.
- Repository-/Store-Klassen kapseln Datenzugriffe und QueryBuilder-Parameter.
- Fachregeln fuer Zeitraeume, Zuordnung und Summen liegen in Services und Value Objects.
- Persistente Kernobjekte nutzen `get(...)`, `get_all([...])`, `toArray()` und nur bei Store-Bindung `save()`.
- Dienste und Termine werden als ein gemeinsamer Kalendereintrag mit explizitem Typ modelliert; die fachliche Darstellung eines externen Termins als Sperrtermin wird abgeleitet und nicht als widerspruechliche zweite Datenwahrheit gespeichert.
- Termine innerhalb eines Dienstes referenzieren diesen explizit ueber `parent_entry_id`; Termine ohne Parent sind Sperrtermine.
- Materialisierte Standarddienste tragen ein eindeutiges Mitarbeiter*innen-/Datumsmerkmal. `default_modified` schuetzt Einzelabweichungen vor spaeterer Seriensynchronisierung; `default_deleted` bewahrt eine Loeschausnahme als Tombstone.
- Bestehende Eintraege duerfen ihren Typ nicht wechseln; Dienst und Termin haben unterschiedliche Folge- und Loeschvertraege.
- Beim Loeschen eines Dienstes muss zwischen gemeinsamem Loeschen der Termine und deren Erhalt als Sperrtermine gewaehlt werden.
- API-Zugriffe liegen im Frontend in Repositories, Daten in Modellen/ViewModels und Rendering/Eventbindung in Komponenten.
- Der Frontend-Unterbau nutzt die vorhandenen LocalBase-Vertraege `ApiClient`, `Repository`, `Model` und `Notice`; AD-Kalender-spezifische API-Pfade, Modelle und Renderinglogik bleiben in diesem Repo.
- Sichtbare Gruppenbezeichnungen, Filter, Leitungsblock, Hierarchiedarstellung und Demo-Gruppenzuordnungen werden aus der gemeinsamen Organisationsdefinition abgeleitet.
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

### Gemeinsame Suite-Navigation

- AD Kalender besitzt keinen eigenen Nextcloud-Hauptnavigationseintrag. `orgsuite` stellt den gemeinsamen Einstieg `AD` bereit.
- Das Template bindet das zentrale OrgSuite-Menue mit `data-suite="ad"` und `data-current-app="adcalendar"` ein.
- Fachliche Lese- und Bearbeitungsrechte bleiben ausschliesslich serverseitig im AD Kalender; Menuesichtbarkeit ist keine Berechtigung.

- Wiederverwendbare app-spezifische Learnings werden Simon zuerst im Standardformat vorgeschlagen.
- Dauerhafte Ergaenzungen dieser Datei erfolgen nur nach ausdruecklicher Freigabe.
- App-uebergreifende Regeln gehoeren in die Parent-`AGENTS.md`.
