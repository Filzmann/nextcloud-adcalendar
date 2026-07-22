# Changelog

## 0.12.0-rc.8

- Umschalter zwischen Wochen- und Monatsansicht ergänzt.
- Monatsansicht als vollständig bedienbare Folge der betroffenen Wochenblöcke mit abgedunkelten Randtagen umgesetzt.
- Personenspalte beim horizontalen Scrollen fixiert; der gewählte Zeitraum wird in URL und persönlichem Standard beibehalten.
- Monatsdaten auf einen serverseitig validierten Bereich von höchstens sechs Wochen begrenzt und mit API-, Zustands-, Navigations- und Layouttests abgesichert.

## 0.12.0-rc.7

- Persönliche Verbindungen zu Kopano, Google, Apple und generischem CalDAV im Einstellungs-Tab ergänzt.
- Zugangsdaten und OAuth-Tokens mit Nextcloud verschlüsselt und als sensible Benutzerwerte gespeichert.
- Sichtbare externe Zielkalender „AD Dienste“, einseitige Mehranbieter-Synchronisierung, Verbindungstest und sicheres Trennen umgesetzt.
- Kopano mit der änderbaren Vorgabe `https://mail.adberlin.org` sowie Apple-/CalDAV-Anleitungen im barrierefreien Dialog ergänzt.
- Google-Webserver-OAuth mit engem Kalender-Scope, einmaligem Statuswert, Offline-Refresh und sicherer Widerrufsstrecke vorbereitet.
- CalDAV-, OAuth-, Geheimnis-, SSRF-/Origin-, Controller-, UI- und Mehranbieter-Verträge getestet.

## 0.12.0-rc.6

- Begrenzte tägliche, wöchentliche und monatliche Terminserien ergänzt.
- Einzel- und Gesamtbearbeitung sowie Einzel- und Gesamtlöschung mit unverändert serverseitiger Rechteprüfung umgesetzt.
- Serienmigration, Sommerzeit-, Urlaubs-, Atomaritäts-, UI- und DDEV-Persistenztests ergänzt.

## 0.12.0-rc.1

- Eigenständige Navigation ohne OrgSuite ergänzt.
- Kalenderfähigkeiten über den optionalen LocalBase-Integrationsvertrag veröffentlicht.
- Ungültige harte App-Abhängigkeiten aus den Nextcloud-Metadaten entfernt.

## 0.11.14-rc.1

- Öffentliche Projekt-, Quellcode- und Fehlerkanäle ergänzt.
- Veröffentlichungsvorbereitung mit neutralisierten Assistenzteam-Beispielen.

## 0.11.13-rc.1

- Erster reproduzierbarer Staging-Releasekandidat für Nextcloud 34 und PHP ab 8.3.
- Dynamische Organisation, Hierarchie und bereichsgebundene Bearbeitungsrechte.
- Standarddienste mit gelöschten Einzelvorkommen sowie Urlaubsintegration.
- DDEV-Integration und authentifizierte HTTP-Rechtematrix.
