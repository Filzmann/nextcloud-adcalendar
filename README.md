# AD Kalender

Wochenbasierte Dienst- und Terminplanung mit Personensuche, Gruppenfiltern, Meetinglückensuche, Standarddienstzeiten und read-only Urlaubsmarkierungen.

## Staging-Kompatibilität

- Nextcloud 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 34 unterstützten Bereichs
- Laufzeitbasis: `localbase`; `orgsuite` ist ab zwei AD-Fachprodukten optional aktiv
- App-ID und Installationsordner: `adcalendar`

## Installation

Für Staging und Auslieferung das Produktbundle `ad-product-adcalendar-<release>.tar.gz` und dessen enthaltenes `install.sh` verwenden. Es prüft und installiert LocalBase automatisch; ab dem zweiten AD-Fachprodukt aktiviert es OrgSuite.

AD Kalender funktioniert einzeln. Ohne AD Urlaub stehen manuelle Sperrtermine zur Verfügung; die read-only Urlaubsmarkierungen entfallen.

Der Befehl `adcalendar:demo:seed` ist ausschließlich für synthetische Testdaten gedacht und darf auf einem realitätsnahen Staging-System nicht ohne bewusste Entscheidung ausgeführt werden.

Installations-, Betriebs- und Abnahmeunterlagen stehen im öffentlichen [AD-Suite-Projekt](https://github.com/Filzmann/ad-suite).
