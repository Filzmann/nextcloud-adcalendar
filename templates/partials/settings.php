<section id="adc-settings-view" class="adc-settings-view" role="tabpanel" aria-labelledby="adc-tab-settings" hidden>
    <section aria-labelledby="adc-calendar-sync-heading">
        <h2 id="adc-calendar-sync-heading">Meine Dienste im Nextcloud-Kalender</h2>
        <p>Der Abgleich ist standardmäßig aktiv. Sobald Dienste vorhanden sind, legt AD Kalender den privaten Kalender „AD Dienste“ an und überträgt ausschließlich deine Dienste; Termine und Urlaube werden nicht synchronisiert. Du kannst den Abgleich hier deaktivieren. AD Kalender bleibt die Quelle der Wahrheit: Änderungen im Zielkalender werden überschrieben und nicht zurückübertragen.</p>
        <form id="adc-calendar-sync-form" class="adc-calendar-sync-form">
            <label for="adc-calendar-sync-enabled"><input id="adc-calendar-sync-enabled" type="checkbox"> Dienste in „AD Dienste“ anzeigen</label>
            <p id="adc-calendar-sync-status" role="status" aria-live="polite">Kalenderstatus wird geladen.</p>
            <button type="submit" class="primary">Kalendersynchronisation speichern</button>
        </form>
    </section>
    <section aria-labelledby="adc-shift-defaults-heading">
        <h2 id="adc-shift-defaults-heading">Meine Standard-Dienstzeiten</h2>
        <p>Diese Zeiten erscheinen als feste Dienste im Kalender und werden beim Anlegen vorgeschlagen. Individuell bearbeitete oder gelöschte Tage bleiben Einzelabweichungen. Liegt das Ende vor dem Beginn, endet der Dienst am Folgetag.</p>
        <form id="adc-shift-defaults-form"><div id="adc-shift-defaults"></div><button type="submit" class="primary">Dienstzeiten speichern</button></form>
    </section>
</section>
