<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('adcalendar', 'admin');
\OCP\Util::addStyle('adcalendar', 'admin');
$calendarSyncStatus = $_['calendarSyncStatus'] ?? ['hasRun' => false, 'lastRunAt' => 0, 'lastRunLabel' => 'Noch kein Hintergrundlauf erfasst', 'attempted' => 0, 'succeeded' => 0, 'failed' => 0, 'state' => 'pending'];
?>
<section id="adcalendar-admin" class="section adc-admin" aria-labelledby="adc-admin-heading">
    <h2 id="adc-admin-heading">AD Kalender</h2>
    <section class="adc-admin-panel" aria-labelledby="adc-calendar-sync-heading">
        <h3 id="adc-calendar-sync-heading">Dienstkalender-Abgleich</h3>
        <p>Der Hintergrundlauf gleicht freigegebene Dienstplaneinträge einseitig aus AD Kalender in die persönlichen DAV-Kalender ab. Die technische Struktur hält eine spätere bidirektionale Erweiterung offen.</p>
        <?php if ($calendarSyncStatus['hasRun']): ?>
            <p class="adc-sync-state adc-sync-state--<?php p($calendarSyncStatus['state']); ?>">Letzter Lauf: <time datetime="<?php p(gmdate(DATE_ATOM, $calendarSyncStatus['lastRunAt'])); ?>"><?php p($calendarSyncStatus['lastRunLabel']); ?></time></p>
            <dl class="adc-sync-summary">
                <div><dt>Geprüft</dt><dd><?php p((string)$calendarSyncStatus['attempted']); ?></dd></div>
                <div><dt>Erfolgreich</dt><dd><?php p((string)$calendarSyncStatus['succeeded']); ?></dd></div>
                <div><dt>Fehlgeschlagen</dt><dd><?php p((string)$calendarSyncStatus['failed']); ?></dd></div>
            </dl>
        <?php else: ?>
            <p class="adc-sync-state"><?php p($calendarSyncStatus['lastRunLabel']); ?>.</p>
        <?php endif; ?>
        <p class="adc-sync-privacy">Keine Konten- oder Kalenderkennungen werden in diesem Status gespeichert oder angezeigt.</p>
    </section>
    <section class="adc-admin-panel" aria-labelledby="adc-demo-heading">
        <h3 id="adc-demo-heading">Demo-Pack</h3>
        <p>Das Demo-Pack legt ausschließlich synthetische lokale Konten, bei Bedarf lokale Gruppen sowie neutrale Dienste und Termine an. Es wird nicht automatisch installiert und importiert keine WordPress-Bestandsdaten.</p>
        <p>Bereits vorhandene fremde Konten und schreibgeschützte LDAP-Gruppen führen vor der ersten Änderung zum Abbruch.</p>
        <p id="adc-demo-notice" class="adc-admin-notice" role="status" aria-live="polite" hidden></p>
        <label class="adc-demo-confirm"><input id="adc-demo-confirm" type="checkbox"> Ich bestätige die Installation synthetischer Demodaten.</label>
        <button id="adc-demo-install" type="button" class="primary" disabled>Kalender-Demo-Pack installieren</button>
    </section>
</section>
