# AD Kalender

Wochen- und monatsbasierte Dienst- und Terminplanung mit wiederkehrenden Terminen, Personensuche, Gruppenfiltern, Meetinglückensuche, Standarddienstzeiten, read-only Urlaubsmarkierungen und persönlichem Dienstexport in Nextcloud sowie externe Kalender.

Die Monatsansicht stellt alle betroffenen Kalenderwochen untereinander dar. Tage außerhalb des gewählten Monats sind abgedunkelt; die Personenspalte bleibt beim horizontalen Scrollen sichtbar. Die Auswahl `Woche` oder `Monat` kann zusammen mit den Filtern als persönlicher Standard gespeichert werden.

## Staging-Kompatibilität

- Nextcloud 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 34 unterstützten Bereichs
- Laufzeitbasis: `localbase`; `orgsuite` ist ab zwei AD-Fachprodukten optional aktiv
- App-ID und Installationsordner: `adcalendar`

## Installation

Für Staging und Auslieferung das Produktbundle `ad-product-adcalendar-<release>.tar.gz` und dessen enthaltenes `install.sh` verwenden. Es prüft und installiert LocalBase automatisch; ab dem zweiten AD-Fachprodukt aktiviert es OrgSuite.

AD Kalender funktioniert einzeln. Ohne AD Urlaub stehen manuelle Sperrtermine zur Verfügung; die read-only Urlaubsmarkierungen entfallen.

Der Befehl `adcalendar:demo:seed` ist ausschließlich für synthetische Testdaten gedacht und darf auf einem realitätsnahen Staging-System nicht ohne bewusste Entscheidung ausgeführt werden.

## Externe Kalender

Jede angemeldete Person verwaltet Kopano-, Google-, Apple- und manuelle CalDAV-Verbindungen im eigenen Tab `Einstellungen`. AD Calendar erzeugt beim Anbieter einen sichtbaren Kalender `AD Dienste` und exportiert ausschließlich Dienste. Anbieterinhalte werden nicht in AD Calendar eingeblendet oder zurückimportiert.

- Kopano ist mit `https://mail.adberlin.org` vorbelegt; die Adresse bleibt im Verbindungsdialog änderbar.
- Apple und manuelles CalDAV verwenden ein Anbieter- beziehungsweise app-spezifisches Passwort.
- CalDAV-Ziele müssen HTTPS verwenden. Nextclouds HTTP-Client erzwingt zusätzlich seine serverseitige SSRF-Sperre.
- Persönliche Passwörter und Google-Tokens liegen verschlüsselt und als sensible Nextcloud-Benutzerkonfiguration vor.

Google benötigt einmalig einen systemweiten Web-OAuth-Client. Die Administration hinterlegt `google_oauth_client_id` und `google_oauth_client_secret` für die App `adcalendar`; das Secret muss als `lazy` und `sensitive` über den geschützten Deploymentweg gesetzt werden und darf nicht in Repository, Shell-Historie oder Dokumentation gelangen. Als autorisierte Redirect-URI dient die von der Zielinstallation erzeugte Route:

    <NEXTCLOUD-BASIS>/index.php/apps/adcalendar/oauth/google/callback

Bei installationsweit aktivem Pretty-URL-Rewriting kann `index.php` entfallen. Maßgeblich ist immer die reale Nextcloud-Basis- und Routingkonfiguration.

## Roadmap

Geplante Erweiterungen und offene Produktentscheidungen stehen in der [Roadmap](ROADMAP.md).

Installations-, Betriebs- und Abnahmeunterlagen stehen im öffentlichen [AD-Suite-Projekt](https://github.com/Filzmann/ad-suite).
