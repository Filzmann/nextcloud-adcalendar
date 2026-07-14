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
