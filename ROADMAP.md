# Roadmap – AD Kalender

Diese Datei bündelt geplante Erweiterungen und offene Produktentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Aktueller Fokus

- Wochenplanung, Meeting-Lückensuche, persönliche Standards und optionale Urlaubsmarkierungen auf einem realitätsnahen Staging fachlich abnehmen.
- Rollen-, Bereichs- und Personenfilter einschließlich bereichsübergreifender Leitungen in der sichtbaren Oberfläche prüfen.

## Geplante Erweiterungen

- Kalenderdienste sollen pro Nutzer*in optional im jeweiligen privaten Nextcloud-Kalender hinterlegt werden können.
- Dieselben Dienste sollen optional mit externen Kalendersystemen wie Kopano synchronisiert werden können.
- Benötigte Auswertungszeiträume über die bestehende Wochenansicht hinaus werden nach einem konkreten Fachbedarf festgelegt.

## Vor der Kalendersynchronisation zu klären

- Quelle der Wahrheit und Synchronisationsrichtung.
- Providerneutrale Anbindung, Authentifizierung und sichere Ablage notwendiger Zugangsdaten.
- Stabile externe Kennungen sowie Verhalten bei Änderung, Löschung, Wiederholung und Konflikten.
- Zeitzonen, Einwilligung, Datenschutz, Protokollierung und Fehlerbehandlung.
- Serverseitige Rechteprüfung; eine Synchronisation erweitert niemals Planungs- oder Leserechte.
