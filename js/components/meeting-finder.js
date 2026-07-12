(function() {
    'use strict';

    /** Zweck: Kapselt Personenauswahl und Ergebnisdarstellung der gemeinsamen Dienstlückensuche. */
    class MeetingFinder {
        constructor(options) {
            this.repository = options.repository;
            this.onError = options.onError;
            this.dialog = document.getElementById('adc-meeting-dialog');
            this.form = document.getElementById('adc-meeting-form');
            this.search = document.getElementById('adc-meeting-search');
            this.people = document.getElementById('adc-meeting-people');
            this.duration = document.getElementById('adc-meeting-duration');
            this.results = document.getElementById('adc-meeting-results');
            this.week = document.getElementById('adc-meeting-week');
            this.employees = [];
            this.selected = new Set();
            this.start = '';
            document.getElementById('adc-meeting-close').addEventListener('click', () => this.close());
            document.getElementById('adc-meeting-cancel').addEventListener('click', () => this.close());
            this.dialog.addEventListener('cancel', event => { event.preventDefault(); this.close(); });
            this.search.addEventListener('input', () => this.renderPeople());
            this.form.addEventListener('submit', event => this.submit(event));
        }

        open(start, employees, selected = []) {
            this.start = start;
            this.employees = employees;
            this.search.value = '';
            this.duration.value = '60';
            this.results.replaceChildren();
            this.selected = new Set(selected);
            this.renderPeople();
            this.week.textContent = `Kalenderwoche ab ${new Date(`${start}T12:00:00`).toLocaleDateString('de-DE')}`;
            this.dialog.showModal();
            this.search.focus();
        }

        close() { this.dialog.close(); }

        renderPeople() {
            const query = this.search.value.trim().toLocaleLowerCase('de-DE');
            this.people.replaceChildren(...this.employees.filter(employee => !query || employee.displayName.toLocaleLowerCase('de-DE').includes(query)).map(employee => {
                const label = document.createElement('label');
                const input = document.createElement('input');
                input.type = 'checkbox'; input.value = employee.uid; input.checked = this.selected.has(employee.uid);
                input.addEventListener('change', () => input.checked ? this.selected.add(employee.uid) : this.selected.delete(employee.uid));
                label.append(input, document.createTextNode(` ${employee.displayName}`));
                return label;
            }));
        }

        async submit(event) {
            event.preventDefault();
            const employeeUids = [...this.selected];
            if (employeeUids.length < 2) {
                this.results.textContent = 'Bitte mindestens zwei Personen auswählen.';
                return;
            }
            try {
                const response = await this.repository.meetingGaps(this.start, employeeUids, Number(this.duration.value));
                this.renderResults(response.gaps || []);
            } catch (error) {
                this.onError(error);
            }
        }

        renderResults(gaps) {
            if (!gaps.length) {
                this.results.textContent = 'In dieser Kalenderwoche wurde keine passende gemeinsame Lücke gefunden.';
                return;
            }
            const heading = document.createElement('h3'); heading.textContent = 'Passende Lücken';
            const list = document.createElement('ul');
            for (const gap of gaps) {
                const start = new Date(gap.start); const end = new Date(gap.end);
                const item = document.createElement('li');
                item.textContent = `${start.toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit' })}, ${this.time(start)}–${this.time(end)} (${gap.durationMinutes} Min.)`;
                list.append(item);
            }
            this.results.replaceChildren(heading, list);
        }

        time(date) { return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' }); }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.MeetingFinder = MeetingFinder;
})();
