# AD Kalender

Wochenbasierte Dienst- und Terminplanung mit Personensuche, Gruppenfiltern, Meetinglückensuche, Standarddienstzeiten und read-only Urlaubsmarkierungen.

## Staging-Kompatibilität

- Nextcloud 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 34 unterstützten Bereichs
- Abhängigkeiten: `localbase`, `orgsuite`
- App-ID und Installationsordner: `adcalendar`

## Installation

```bash
sudo -u www-data php occ app:enable localbase
sudo -u www-data php occ app:enable orgsuite
sudo -u www-data php occ app:enable adcalendar
```

Der Befehl `adcalendar:demo:seed` ist ausschließlich für synthetische Testdaten gedacht und darf auf einem realitätsnahen Staging-System nicht ohne bewusste Entscheidung ausgeführt werden.
