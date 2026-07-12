<?php
script('adcalendar', 'main');
style('adcalendar', 'style');
?>
<div id="adcalendar-app" class="adc-app">
    <header class="adc-header">
        <div>
            <h1>AD Kalender</h1>
            <p>Dienste, Termine und Sperrtermine im Wochenueberblick</p>
        </div>
        <nav aria-label="Kalenderwoche" class="adc-navigation">
            <button type="button" id="adc-previous-week">Vorherige Woche</button>
            <output id="adc-week-label" aria-live="polite"></output>
            <button type="button" id="adc-next-week">Naechste Woche</button>
        </nav>
    </header>
    <div id="adc-notice" role="status" aria-live="polite"></div>
    <section class="adc-create" aria-labelledby="adc-create-heading">
        <h2 id="adc-create-heading">Eintrag anlegen</h2>
        <form id="adc-entry-form">
            <label>Mitarbeiter*in <select id="adc-employee" required></select></label>
            <label>Typ <select id="adc-type"><option value="shift">Dienst</option><option value="appointment">Termin / Sperrtermin</option></select></label>
            <label>Beginn <input id="adc-start" type="datetime-local" required></label>
            <label>Ende <input id="adc-end" type="datetime-local" required></label>
            <label>Titel <input id="adc-title" maxlength="255" aria-describedby="adc-title-help"></label>
            <small id="adc-title-help">Bei Terminen erforderlich. Termine ausserhalb eines Dienstes erscheinen als Sperrtermin.</small>
            <button type="submit">Speichern</button>
        </form>
    </section>
    <section aria-labelledby="adc-overview-heading">
        <h2 id="adc-overview-heading">Wochenplan</h2>
        <div class="adc-table-wrap">
            <table class="adc-calendar">
                <caption>Geplante Dienste und Termine je Mitarbeiter*in</caption>
                <thead id="adc-calendar-head"></thead>
                <tbody id="adc-calendar-body">
                    <tr><td>Daten werden geladen.</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
