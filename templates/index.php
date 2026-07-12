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
            <label>KW <input id="adc-week-number" type="week"></label>
            <button type="button" id="adc-next-week">Naechste Woche</button>
            <button type="button" id="adc-toggle-view" aria-pressed="false">Tage als Zeilen</button>
        </nav>
    </header>
    <div id="adc-notice" role="status" aria-live="polite"></div>
    <section class="adc-filters" aria-labelledby="adc-filter-heading">
        <h2 id="adc-filter-heading">Ansicht filtern und vergleichen</h2>
        <fieldset><legend>Rollen</legend><div id="adc-role-filters"></div></fieldset>
        <fieldset><legend>Bereiche</legend><div id="adc-area-filters"></div></fieldset>
        <label for="adc-person-search">Person suchen</label>
        <input id="adc-person-search" type="search" autocomplete="off" aria-controls="adc-search-results">
        <ul id="adc-search-results" class="adc-search-results"></ul>
        <h3>Ausgewaehlte Personen</h3>
        <ul id="adc-selected-people" class="adc-selected-people"><li>Keine explizite Auswahl – Gruppenfilter gelten.</li></ul>
    </section>
    <section class="adc-create" aria-labelledby="adc-create-heading">
        <h2 id="adc-create-heading">Eintrag anlegen</h2>
        <form id="adc-entry-form">
            <input id="adc-entry-id" type="hidden">
            <label>Mitarbeiter*in <select id="adc-employee" required></select></label>
            <label>Typ <select id="adc-type"><option value="shift">Dienst</option><option value="appointment">Termin / Sperrtermin</option></select></label>
            <label>Beginn <input id="adc-start" type="datetime-local" required></label>
            <label>Ende <input id="adc-end" type="datetime-local" required></label>
            <label>Titel <input id="adc-title" maxlength="255" aria-describedby="adc-title-help"></label>
            <small id="adc-title-help">Bei Terminen erforderlich. Termine ausserhalb eines Dienstes erscheinen als Sperrtermin.</small>
            <button type="submit">Speichern</button>
            <button type="button" id="adc-cancel-edit" hidden>Bearbeitung abbrechen</button>
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
    <section id="adc-settings" class="adc-settings" aria-labelledby="adc-settings-heading" hidden>
        <h2 id="adc-settings-heading">Bearbeitungsrechte innerhalb von Fachgruppen</h2>
        <p>Ist ein Schalter aktiv, duerfen Mitglieder dieser Gruppe gegenseitig ihre Kalenderdaten bearbeiten.</p>
        <form id="adc-settings-form"><div id="adc-peer-settings"></div><button type="submit">Einstellungen speichern</button></form>
    </section>
</div>
