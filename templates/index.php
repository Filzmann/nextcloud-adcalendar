<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('localbase', 'models/model');
\OCP\Util::addScript('localbase', 'repositories/repository');
\OCP\Util::addScript('localbase', 'ui/ui');
\OCP\Util::addScript('orgsuite', 'suite-navigation');
\OCP\Util::addScript('adcalendar', 'models/calendar-entry');
\OCP\Util::addScript('adcalendar', 'models/organization');
\OCP\Util::addScript('adcalendar', 'repositories/calendar-repository');
\OCP\Util::addScript('adcalendar', 'modules/calendar-date');
\OCP\Util::addScript('adcalendar', 'modules/calendar-state');
\OCP\Util::addScript('adcalendar', 'modules/calendar-timeline');
\OCP\Util::addScript('adcalendar', 'modules/entry-workflow');
\OCP\Util::addScript('adcalendar', 'modules/meeting-capabilities');
\OCP\Util::addScript('adcalendar', 'components/calendar-filters');
\OCP\Util::addScript('adcalendar', 'components/calendar-cell');
\OCP\Util::addScript('adcalendar', 'components/entry-dialog');
\OCP\Util::addScript('adcalendar', 'components/meeting-finder');
\OCP\Util::addScript('adcalendar', 'components/shift-defaults');
\OCP\Util::addScript('adcalendar', 'components/tab-navigation');
\OCP\Util::addScript('adcalendar', 'components/week-navigation');
\OCP\Util::addScript('adcalendar', 'components/week-table');
\OCP\Util::addScript('adcalendar', 'main');
\OCP\Util::addStyle('adcalendar', 'style');
\OCP\Util::addStyle('orgsuite', 'suite-navigation');
?>
<div id="adcalendar-app" class="adc-app">
    <div class="orgsuite-host" data-orgsuite data-suite="ad" data-current-app="adcalendar"></div>
    <header class="adc-header">
        <div>
            <h1>AD Kalender</h1>
            <p>Dienste, Termine und Sperrtermine im Wochenüberblick</p>
        </div>
    </header>
    <div id="adc-notice" role="status" aria-live="polite"></div>
    <nav class="adc-tabs" role="tablist" aria-label="AD Kalender Bereiche">
        <button type="button" id="adc-tab-calendar" role="tab" aria-controls="adc-calendar-view" aria-selected="true">Kalender</button>
        <button type="button" id="adc-tab-settings" role="tab" aria-controls="adc-settings-view" aria-selected="false">Einstellungen</button>
    </nav>
    <section id="adc-calendar-view" role="tabpanel" aria-labelledby="adc-tab-calendar">
      <details class="adc-filters">
        <summary id="adc-filter-heading">Filter und Personenvergleich <span id="adc-filter-status" class="adc-filter-status">Alle Personen</span></summary>
        <div class="adc-filter-grid" aria-labelledby="adc-filter-heading">
            <fieldset><legend>Rollen</legend><div id="adc-role-filters"></div></fieldset>
            <fieldset><legend>Bereiche</legend><div id="adc-area-filters"></div></fieldset>
            <div class="adc-person-filter">
                <label for="adc-person-search">Person suchen</label>
                <input id="adc-person-search" type="search" autocomplete="off" aria-controls="adc-search-results">
                <ul id="adc-search-results" class="adc-search-results"></ul>
            </div>
            <div class="adc-selection-filter">
                <strong>Ausgewählte Personen</strong>
                <ul id="adc-selected-people" class="adc-selected-people"><li>Keine explizite Auswahl – Gruppenfilter gelten.</li></ul>
                <div class="adc-selection-actions">
                    <button type="button" id="adc-reset-selection" hidden><span class="adc-button-icon icon-close" aria-hidden="true"></span><span>Auswahl zurücksetzen</span></button>
                    <button type="button" id="adc-open-meeting-finder" disabled><span class="adc-button-icon icon-calendar-dark" aria-hidden="true"></span><span>Meetinglücke finden</span></button>
                </div>
            </div>
            <div class="adc-filter-actions">
                <button type="button" id="adc-save-default">Zum Standard machen</button>
            </div>
        </div>
      </details>
      <section class="adc-overview" aria-labelledby="adc-overview-heading">
        <div class="adc-overview-header">
            <h2 id="adc-overview-heading">Wochenplan</h2>
            <nav aria-label="Kalenderwoche" class="adc-navigation">
                <button type="button" id="adc-previous-week">Vorherige Woche</button>
                <output id="adc-week-label" aria-live="polite"></output>
                <label>KW <input id="adc-week-number" type="week"></label>
                <button type="button" id="adc-next-week">Nächste Woche</button>
                <button type="button" id="adc-toggle-view" aria-pressed="false">Tage als Zeilen</button>
            </nav>
        </div>
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
    </section>
    <section id="adc-settings-view" class="adc-settings-view" role="tabpanel" aria-labelledby="adc-tab-settings" hidden>
        <section aria-labelledby="adc-shift-defaults-heading">
            <h2 id="adc-shift-defaults-heading">Meine Standard-Dienstzeiten</h2>
            <p>Diese Zeiten erscheinen als feste Dienste im Kalender und werden beim Anlegen vorgeschlagen. Individuell bearbeitete oder gelöschte Tage bleiben Einzelabweichungen. Liegt das Ende vor dem Beginn, endet der Dienst am Folgetag.</p>
            <form id="adc-shift-defaults-form"><div id="adc-shift-defaults"></div><button type="submit" class="primary">Dienstzeiten speichern</button></form>
        </section>
    </section>
    <dialog id="adc-entry-dialog" class="adc-dialog" aria-labelledby="adc-entry-dialog-title">
        <form id="adc-entry-form" method="dialog">
            <header class="adc-dialog__header">
                <h2 id="adc-entry-dialog-title">Eintrag</h2>
                <button type="button" id="adc-cancel-edit" class="adc-icon-button" aria-label="Dialog schließen" title="Schließen">×</button>
            </header>
            <input id="adc-entry-id" type="hidden">
            <div class="adc-dialog__fields">
                <label>Mitarbeiter*in <select id="adc-employee" required></select></label>
                <label>Typ <select id="adc-type"><option value="shift">Dienst</option><option value="appointment">Termin / Sperrtermin</option></select></label>
                <label>Beginn <input id="adc-start" type="datetime-local" required aria-describedby="adc-time-help"></label>
                <label>Ende <input id="adc-end" type="datetime-local" required aria-describedby="adc-time-help"></label>
                <label id="adc-title-field"><span id="adc-title-label">Titel</span><input id="adc-title" maxlength="255" aria-describedby="adc-title-help"></label>
            </div>
            <small id="adc-title-help">Titel ist bei Diensten optional.</small>
            <p id="adc-time-help" class="adc-dialog__hint" aria-live="polite"></p>
            <footer class="adc-dialog__actions">
                <button type="button" id="adc-dialog-cancel">Abbrechen</button>
                <button type="submit" class="primary">Speichern</button>
            </footer>
        </form>
    </dialog>
    <dialog id="adc-meeting-dialog" class="adc-dialog adc-meeting-dialog" aria-labelledby="adc-meeting-dialog-title">
        <form id="adc-meeting-form">
            <header class="adc-dialog__header">
                <h2 id="adc-meeting-dialog-title">Meetinglücke finden</h2>
                <button type="button" id="adc-meeting-close" class="adc-icon-button icon-close" aria-label="Dialog schließen" title="Schließen"></button>
            </header>
            <p id="adc-meeting-week"></p>
            <label for="adc-meeting-search">Teilnehmende suchen</label>
            <input id="adc-meeting-search" type="search" autocomplete="off">
            <fieldset class="adc-meeting-people"><legend>Mindestens zwei Personen</legend><div id="adc-meeting-people"></div></fieldset>
            <label>Dauer in Minuten <input id="adc-meeting-duration" type="number" min="15" max="480" step="15" value="60" required></label>
            <label>Titel für die Blockierung <input id="adc-meeting-title" maxlength="255" placeholder="z. B. Teamsitzung"></label>
            <div class="adc-dialog__actions">
                <button type="button" id="adc-meeting-cancel">Abbrechen</button>
                <button type="submit" class="primary">Lücken suchen</button>
            </div>
            <div id="adc-meeting-results" class="adc-meeting-results" aria-live="polite"></div>
        </form>
    </dialog>
</div>
