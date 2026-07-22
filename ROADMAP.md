# Roadmap – AD Kalender

Diese Datei bündelt geplante Erweiterungen und offene Produktentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Aktueller Fokus

- Wochen- und Monatsplanung, Meeting-Lückensuche, persönliche Standards und optionale Urlaubsmarkierungen auf einem realitätsnahen Staging fachlich abnehmen.
- Rollen-, Bereichs- und Personenfilter einschließlich bereichsübergreifender Leitungen in der sichtbaren Oberfläche prüfen.
- Die ergänzten globalen Gruppen Stv. PDL, Büroorganisation Pflege, Fahrzeugverwaltung und Empfang mit ihrer Backend-Reihenfolge und Hierarchie im Kalender abnehmen.
- Den einseitigen Abgleich persönlicher Dienste in den privaten Nextcloud-Kalender „AD Dienste“ fachlich abnehmen.
- Persönliche Kopano- und manuelle CalDAV-Verbindungen mit realen Testkonten auf Staging fachlich abnehmen.
- Die fachliche Abnahme der Google- und Apple-Verbindungen ist auf Mitte bis Ende August 2026 verschoben.

## Umgesetzte Synchronisationsstufe

- Der Abgleich eigener Dienste ist standardmäßig aktiv. Jede angemeldete Person kann ihn im Einstellungs-Tab als persönliches Opt-out deaktivieren und später wieder aktivieren.
- Bei vorhandenen Diensten erzeugt der Abgleich den privaten Nextcloud-Kalender „AD Dienste“ und übernimmt den vollständigen Dienstbestand. Danach werden nur AD-Dienste erstellt, aktualisiert und gelöscht; Termine und Urlaube werden nicht übertragen.
- Berechtigte Planer*innen können Dienste weiterhin nach dem normalen Rechtevertrag ändern. Die Veröffentlichung erfolgt ausschließlich aufgrund dieser bereits erlaubten AD-Mutation im Kalender der zugeordneten Person und erweitert weder Lese- noch Schreibrechte.
- Deterministische Kalender-, Objekt- und Ereigniskennungen machen Wiederholungen idempotent und halten eine spätere bidirektionale Erweiterung offen.
- Der interne Nextcloud-DAV-Zugriff ist in einem austauschbaren Provideradapter isoliert. Fremde Objekte in „AD Dienste“ bleiben unangetastet; beim Opt-out wird der Kalender nur entfernt, wenn danach keine fremden Objekte verbleiben.
- AD Kalender bleibt auch bei einem DAV-Fehler führend. Die fachliche Änderung wird gespeichert und der Übertragungsfehler sicher protokolliert.
- Ein nicht paralleler Nextcloud-Hintergrundjob wird alle 15 Minuten erneut fällig und gleicht standardmäßig aktive Konten mit Diensten sowie ausdrücklich aktivierte Konten vollständig mit dem führenden AD-Dienstbestand ab; gespeicherte Opt-outs bleiben ausgeschlossen. Der tatsächliche Start hängt von der konfigurierten Nextcloud-Cron-Ausführung ab. Bei einem späteren bidirektionalen Ausbau bleibt dieser Lauf als ausgehender Konsistenzschritt nach Import und Konfliktauflösung erhalten.
- Der Adminbereich zeigt den letzten Lauf nur aggregiert mit Zeitpunkt sowie geprüften, erfolgreichen und fehlgeschlagenen Abgleichen. Es werden keine Konto- oder Kalenderkennungen persistiert; die gespeicherte Richtung erlaubt später getrennte Import-/Export-Aggregate.
- Im persönlichen Einstellungs-Tab können Kopano, Google, Apple und generisches CalDAV parallel verbunden werden. Kopano verwendet die änderbare Vorgabe `https://mail.adberlin.org`; Apple und manuelles CalDAV erklären die erforderlichen Zugangsdaten im Dialog.
- Externe Anbieter erhalten einen sichtbaren, app-eigenen Kalender „AD Dienste“. Ihre Kalender werden nicht als zusätzliche Ansichten in AD Calendar eingeblendet.
- CalDAV-Zugangsdaten und Google-Refresh-Tokens werden mit Nextclouds `ICrypto` verschlüsselt und als sensible persönliche Konfiguration gespeichert. Statusantworten und Logs enthalten keine Geheimnisse oder Kontokennungen.
- Google verwendet Webserver-OAuth mit einmaligem, nutzergebundenem Statuswert, Offline-Zugriff und dem auf app-erzeugte Kalender begrenzten Scope. Ohne systemweit hinterlegten OAuth-Client bleibt die persönliche Schaltfläche sichtbar, aber deaktiviert.
- Providerfehler bleiben voneinander und von der führenden AD-Mutation isoliert. Der Hintergrundlauf bezieht verbundene externe Konten auch dann ein, wenn ihr interner Nextcloud-Kalender deaktiviert wurde.
- Der Adminbereich enthält einen rein lesenden Kopano-CalDAV-Verbindungstest. Er verwendet denselben URL-Vertrag wie der persönliche Connector, speichert keine Testzugangsdaten und erklärt insbesondere einen vom Betreiber abgewiesenen HTTP-405-Zugriff.

## Umgesetzte Terminserien

- Einzeltermine und Sperrtermine können täglich, wöchentlich oder monatlich mit Intervall, ausgewählten Wochentagen und verpflichtendem Enddatum wiederholt werden.
- Serien sind auf 500 Vorkommen begrenzt und werden nach vollständiger Rechte-, Urlaubs- und Dienstzuordnungsprüfung atomar materialisiert. Die lokale Uhrzeit bleibt über Zeitumstellungen stabil; nicht vorhandene Monatstage werden ausgelassen.
- Einzelne Vorkommen können als Ausnahme oder gemeinsam mit der vollständigen Serie bearbeitet und gelöscht werden. Gemeinsame Meeting-Blöcke und Dienste bleiben außerhalb dieses Serienvertrags.
- „Dieses und folgende“ sowie eine nachträgliche Änderung des Wiederholungsmusters bleiben mögliche spätere Erweiterungen nach konkretem Fachbedarf.

## Umgesetzte Ansichtszeiträume

- Zwischen Wochen- und Monatsansicht kann direkt in der Kalendernavigation umgeschaltet werden; der Zeitraum ist Teil des persönlichen Standards.
- Der Wechsel „Tage als Zeilen / Personen als Zeilen“ steht in Wochen- und Monatsansicht zur Verfügung und ist bereits Teil des persönlichen Standards.
- Die Monatsansicht zeigt die betroffenen Wochenblöcke untereinander und dimmt Tage außerhalb des gewählten Monats.
- Die zu den Personen gehörende Kopfzeile beziehungsweise erste Spalte bleibt in beiden Ausrichtungen beim Scrollen sichtbar.
- Samstag und Sonntag sind in beiden Ansichten und Ausrichtungen als Wochenendflächen und zusätzlich mit dem Text „Wochenende“ gekennzeichnet. Anlegen, Bearbeiten und Löschen verwenden unverändert den bestehenden serverseitigen Rechtevertrag.
- Gesetzliche Berliner Feiertage erscheinen in beiden Ansichten und Ausrichtungen mit Namen und eigener Markierung. Die Regeln werden lokal aus § 1 Feiertagsgesetz Berlin einschließlich beweglicher Feiertage berechnet; der einmalige Feiertag am 17. Juni 2028 ist berücksichtigt. Feiertage verändern weder Dienste noch Termine, Verfügbarkeit oder Berechtigungen.
- Die Meeting-Lückensuche bleibt bewusst auf die ausgewählte Wochenansicht begrenzt.

## Geplante Erweiterungen

- Für den Produktivbetrieb ist noch festzulegen, wie fehlgeschlagene Hintergrundläufe überwacht und administrativ sichtbar gemacht werden.
- Weitere Auswertungszeiträume über Woche und Monat hinaus werden nach einem konkreten Fachbedarf festgelegt.

## Festgelegte Synchronisationsleitplanken

- AD Kalender bleibt zunächst die alleinige Quelle der Wahrheit; Dienste werden nur aus AD Kalender in angebundene Kalender übertragen.
- Die erste Ausbaustufe importiert keine Änderungen aus privaten Nextcloud- oder externen Kalendern.
- Provideradapter und stabile Zuordnungskennungen halten eine spätere bidirektionale Synchronisation offen.

## Vor bidirektionalen Synchronisationsstufen zu klären

- Konfliktauflösung, Löschungen und Zuständigkeit bei einem späteren Rückimport.
- Einwilligung, Datenschutz, Monitoring und Wiederholungsstrategie für einen späteren Rückimport.
- Serverseitige Rechteprüfung; eine Synchronisation erweitert niemals Planungs- oder Leserechte.
