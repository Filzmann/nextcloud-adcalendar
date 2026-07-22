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
        <fieldset id="adc-recurrence-fields" class="adc-recurrence" hidden>
            <legend>Wiederholung</legend>
            <label for="adc-recurrence-frequency">Häufigkeit</label>
            <select id="adc-recurrence-frequency">
                <option value="">Einmalig</option>
                <option value="daily">Täglich</option>
                <option value="weekly">Wöchentlich</option>
                <option value="monthly">Monatlich</option>
            </select>
            <div id="adc-recurrence-options" class="adc-recurrence__options" hidden>
                <label for="adc-recurrence-interval">Intervall</label>
                <input id="adc-recurrence-interval" type="number" min="1" max="365" value="1" inputmode="numeric">
                <label for="adc-recurrence-until">Enddatum</label>
                <input id="adc-recurrence-until" type="date">
                <fieldset id="adc-recurrence-weekdays" class="adc-recurrence__weekdays" hidden>
                    <legend>Wochentage</legend>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="1"> Mo</label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="2"> Di</label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="3"> Mi</label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="4"> Do</label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="5"> Fr</label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="6"> Sa</label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="7"> So</label>
                </fieldset>
                <small>Mindestens zwei, höchstens 500 Vorkommen. Monate ohne den gewählten Kalendertag werden ausgelassen.</small>
            </div>
        </fieldset>
        <small id="adc-title-help">Titel ist bei Diensten optional.</small>
        <p id="adc-time-help" class="adc-dialog__hint" aria-live="polite"></p>
        <footer class="adc-dialog__actions">
            <button type="button" id="adc-dialog-cancel">Abbrechen</button>
            <button type="submit" class="primary">Speichern</button>
        </footer>
    </form>
</dialog>
