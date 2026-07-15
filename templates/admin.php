<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('adcalendar', 'admin');
\OCP\Util::addStyle('adcalendar', 'admin');
?>
<section id="adcalendar-admin" class="section adc-admin" aria-labelledby="adc-admin-heading">
    <h2 id="adc-admin-heading">AD Kalender</h2>
    <section class="adc-admin-panel" aria-labelledby="adc-demo-heading">
        <h3 id="adc-demo-heading">Demo-Pack</h3>
        <p>Das Demo-Pack legt ausschließlich synthetische lokale Konten, bei Bedarf lokale Gruppen sowie neutrale Dienste und Termine an. Es wird nicht automatisch installiert und importiert keine WordPress-Bestandsdaten.</p>
        <p>Bereits vorhandene fremde Konten und schreibgeschützte LDAP-Gruppen führen vor der ersten Änderung zum Abbruch.</p>
        <p id="adc-demo-notice" class="adc-admin-notice" role="status" aria-live="polite" hidden></p>
        <label class="adc-demo-confirm"><input id="adc-demo-confirm" type="checkbox"> Ich bestätige die Installation synthetischer Demodaten.</label>
        <button id="adc-demo-install" type="button" class="primary" disabled>Kalender-Demo-Pack installieren</button>
    </section>
</section>
