(function() {
    'use strict';

    /** Zweck: Bedient das persönliche Opt-in für den privaten Nextcloud-Kalender „AD Dienste“. */
    class ShiftCalendarSync {
        constructor(options) {
            this.form = document.getElementById('adc-calendar-sync-form');
            this.input = document.getElementById('adc-calendar-sync-enabled');
            this.status = document.getElementById('adc-calendar-sync-status');
            this.onSave = options.onSave;
            this.form.addEventListener('submit', event => this.submit(event));
        }

        set(status) {
            this.input.checked = Boolean(status.enabled);
            const name = status.calendarName || 'AD Dienste';
            this.status.textContent = status.enabled ? `Kalender ist aktiv: ${name}.` : `Kalender ist nicht aktiv: ${name}.`;
        }

        async submit(event) {
            event.preventDefault();
            if (this.form.reportValidity()) await this.onSave(this.input.checked);
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.ShiftCalendarSync = ShiftCalendarSync;
})();
