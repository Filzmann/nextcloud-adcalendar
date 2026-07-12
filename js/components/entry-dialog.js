(function() {
    'use strict';

    /**
     * Zweck: Kapselt den typabhängigen Eintragsdialog und verhindert erkennbare Dienstüberschneidungen bereits vor dem API-Aufruf.
     * Spiegelung: PHP: CalendarService::save() bleibt die autoritative Überschneidungsprüfung.
     */
    class EntryDialog {
        constructor(options) {
            this.dialog = document.getElementById('adc-entry-dialog');
            this.form = document.getElementById('adc-entry-form');
            this.entries = options.entries;
            this.shiftDefaults = options.shiftDefaults;
            this.onSubmit = options.onSubmit;
            this.fields = Object.fromEntries(['entry-id', 'employee', 'type', 'start', 'end', 'title', 'title-field', 'title-label', 'title-help', 'time-help', 'entry-dialog-title'].map(id => [id, document.getElementById(`adc-${id}`)]));
            document.getElementById('adc-cancel-edit').addEventListener('click', () => this.close());
            document.getElementById('adc-dialog-cancel').addEventListener('click', () => this.close());
            this.dialog.addEventListener('cancel', event => { event.preventDefault(); this.close(); });
            this.fields.type.addEventListener('change', () => { this.updateType(); this.validate(); });
            this.fields.employee.addEventListener('change', () => this.validate());
            this.fields.start.addEventListener('input', () => this.validate());
            this.fields.end.addEventListener('input', () => this.validate());
            this.form.addEventListener('submit', event => this.submit(event));
        }

        setEmployees(employees) {
            this.fields.employee.replaceChildren(...employees.map(employee => {
                const option = document.createElement('option');
                option.value = employee.uid;
                option.textContent = employee.displayName;
                return option;
            }));
        }

        open({ employee, day, type, entry = null }) {
            this.form.reset();
            this.fields['entry-id'].value = entry?.id || '';
            this.fields.employee.value = entry?.employeeUid || employee.uid;
            this.fields.type.value = entry?.type || type;
            this.fields.type.disabled = Boolean(entry);
            if (entry) {
                this.fields.start.value = this.localDateTime(entry.start);
                this.fields.end.value = this.localDateTime(entry.end);
                this.fields.title.value = entry.title;
            } else {
                const range = type === 'shift' ? this.nextFreeShift(employee.uid, day) : this.defaultAppointment(day);
                this.fields.start.value = this.localDateTime(range.start);
                this.fields.end.value = this.localDateTime(range.end);
            }
            this.updateType();
            this.validate();
            this.dialog.showModal();
            this.fields.start.focus();
        }

        close() {
            this.dialog.close();
            this.form.reset();
            this.fields['entry-id'].value = '';
            this.fields.type.disabled = false;
        }

        updateType() {
            const appointment = this.fields.type.value === 'appointment';
            const editing = this.fields['entry-id'].value !== '';
            this.fields['entry-dialog-title'].textContent = `${editing ? 'Bearbeiten' : 'Anlegen'}: ${appointment ? 'Termin / Sperrtermin' : 'Dienst'}`;
            this.fields['title-label'].textContent = appointment ? 'Titel (erforderlich)' : 'Titel (optional)';
            this.fields['title-help'].textContent = appointment
                ? 'Außerhalb eines Dienstes wird der Termin als Sperrtermin angezeigt.'
                : 'Ein Dienst kann ohne Titel gespeichert werden.';
            this.fields.title.required = appointment;
        }

        validate() {
            this.fields.start.setCustomValidity('');
            this.fields.end.setCustomValidity('');
            this.fields['time-help'].textContent = '';
            if (this.fields.type.value !== 'shift' || !this.fields.start.value || !this.fields.end.value) return true;
            const start = new Date(this.fields.start.value);
            const end = new Date(this.fields.end.value);
            const currentId = Number(this.fields['entry-id'].value || 0);
            if (end <= start) return true;
            const conflict = this.entries().find(entry => entry.type === 'shift'
                && entry.employeeUid === this.fields.employee.value
                && entry.id !== currentId
                && start < new Date(entry.end)
                && end > new Date(entry.start));
            if (!conflict) return true;
            const message = `Überschneidung mit Dienst ${this.time(conflict.start)}–${this.time(conflict.end)}.`;
            this.fields.start.setCustomValidity(message);
            this.fields.end.setCustomValidity(message);
            this.fields['time-help'].textContent = message;
            return false;
        }

        async submit(event) {
            event.preventDefault();
            if (!this.validate() || !this.form.reportValidity()) return;
            await this.onSubmit({
                id: this.fields['entry-id'].value || null,
                employeeUid: this.fields.employee.value,
                type: this.fields.type.value,
                start: new Date(this.fields.start.value).toISOString(),
                end: new Date(this.fields.end.value).toISOString(),
                title: this.fields.title.value,
            });
        }

        nextFreeShift(employeeUid, day) {
            const weekday = day.getDay() || 7;
            const defaults = this.shiftDefaults()?.[String(weekday)] || { enabled: true, start: '08:00', end: '16:00' };
            const startTime = defaults.enabled === false ? '08:00' : defaults.start;
            const endTime = defaults.enabled === false ? '16:00' : defaults.end;
            let start = this.atTime(day, startTime);
            let end = this.atTime(day, endTime);
            if (end <= start) end.setDate(end.getDate() + 1);
            const duration = end.getTime() - start.getTime();
            const shifts = this.entries().filter(entry => entry.type === 'shift' && entry.employeeUid === employeeUid).sort((a, b) => new Date(a.start) - new Date(b.start));
            let conflict;
            while ((conflict = shifts.find(entry => start < new Date(entry.end) && end > new Date(entry.start)))) {
                start = new Date(conflict.end);
                end = new Date(start.getTime() + duration);
            }
            return { start, end };
        }

        atTime(day, value) {
            const [hour, minute] = String(value || '').split(':').map(Number);
            const result = new Date(day); result.setHours(hour || 0, minute || 0, 0, 0); return result;
        }

        defaultAppointment(day) {
            const start = new Date(day); start.setHours(9, 0, 0, 0);
            const end = new Date(start); end.setHours(10, 0, 0, 0);
            return { start, end };
        }

        localDateTime(value) {
            const date = new Date(value);
            const shifted = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
            return shifted.toISOString().slice(0, 16);
        }

        time(value) {
            return new Date(value).toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.EntryDialog = EntryDialog;
})();
