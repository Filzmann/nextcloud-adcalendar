# Roadmap – AD Kalender

Diese Datei bündelt geplante Erweiterungen und offene Produktentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Aktueller Fokus

- Wochenplanung, Meeting-Lückensuche, persönliche Standards und optionale Urlaubsmarkierungen auf einem realitätsnahen Staging fachlich abnehmen.
- Rollen-, Bereichs- und Personenfilter einschließlich bereichsübergreifender Leitungen in der sichtbaren Oberfläche prüfen.
- Den einseitigen Abgleich persönlicher Dienste in den privaten Nextcloud-Kalender „AD Dienste“ fachlich abnehmen.

## Umgesetzte Synchronisationsstufe

- Jede angemeldete Person kann den Abgleich der eigenen Dienste im Einstellungs-Tab persönlich aktivieren und wieder deaktivieren. Ohne Opt-in wird kein Kalender angelegt.
- Das Opt-in erzeugt den privaten Nextcloud-Kalender „AD Dienste“ und übernimmt vorhandene Dienste. Danach werden nur AD-Dienste erstellt, aktualisiert und gelöscht; Termine und Urlaube werden nicht übertragen.
- Berechtigte Planer*innen können Dienste weiterhin nach dem normalen Rechtevertrag ändern. Die Veröffentlichung erfolgt ausschließlich aufgrund dieser bereits erlaubten AD-Mutation im Kalender der zugeordneten Person und erweitert weder Lese- noch Schreibrechte.
- Deterministische Kalender-, Objekt- und Ereigniskennungen machen Wiederholungen idempotent und halten eine spätere bidirektionale Erweiterung offen.
- Der interne Nextcloud-DAV-Zugriff ist in einem austauschbaren Provideradapter isoliert. Fremde Objekte in „AD Dienste“ bleiben unangetastet; beim Opt-out wird der Kalender nur entfernt, wenn danach keine fremden Objekte verbleiben.
- AD Kalender bleibt auch bei einem DAV-Fehler führend. Die fachliche Änderung wird gespeichert und der Übertragungsfehler sicher protokolliert.

## Geplante Erweiterungen

- Dieselben Dienste sollen optional mit externen Kalendersystemen wie Kopano synchronisiert werden können.
- Für vorübergehende DAV-Fehler ist vor einem Produktivbetrieb ein beobachtbarer Wiederholungs- und Nachholmechanismus festzulegen.
- Benötigte Auswertungszeiträume über die bestehende Wochenansicht hinaus werden nach einem konkreten Fachbedarf festgelegt.

## Festgelegte Synchronisationsleitplanken

- AD Kalender bleibt zunächst die alleinige Quelle der Wahrheit; Dienste werden nur aus AD Kalender in angebundene Kalender übertragen.
- Die erste Ausbaustufe importiert keine Änderungen aus privaten Nextcloud- oder externen Kalendern.
- Provideradapter und stabile Zuordnungskennungen halten eine spätere bidirektionale Synchronisation offen.

## Vor externen oder bidirektionalen Synchronisationsstufen zu klären

- Providerneutrale Anbindung externer Systeme, Authentifizierung und sichere Ablage notwendiger Zugangsdaten.
- Konfliktauflösung, Löschungen und Zuständigkeit bei einem späteren Rückimport.
- Einwilligung, Datenschutz, Protokollierung, Monitoring und Wiederholungsstrategie für externe Anbieter.
- Serverseitige Rechteprüfung; eine Synchronisation erweitert niemals Planungs- oder Leserechte.
