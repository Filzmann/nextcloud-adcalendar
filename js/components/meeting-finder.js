(function() {
    'use strict';
    const CalendarDate = window.AdCalendar.modules.CalendarDate;

    /**
     * Zweck: Kapselt Personenauswahl, wochenweise Lückensuche und gemeinsame Terminblockierung.
     * Vertrag: Eine Blockierung wird nur angeboten, wenn der Server alle ausgewählten Kalender zur Bearbeitung freigibt.
     */
    class MeetingFinder {
        constructor(options) {
            this.repository = options.repository;
            this.onError = options.onError;
            this.onBlocked = options.onBlocked || (() => {});
            this.dialog = document.getElementById('adc-meeting-dialog');
            this.form = document.getElementById('adc-meeting-form');
            this.search = document.getElementById('adc-meeting-search');
            this.people = document.getElementById('adc-meeting-people');
            this.duration = document.getElementById('adc-meeting-duration');
            this.title = document.getElementById('adc-meeting-title');
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
            this.results.replaceChildren();
            this.selected = new Set(selected);
            this.renderPeople();
            this.renderWeek();
            this.dialog.showModal();
            this.search.focus();
        }

        close() { this.dialog.close(); }

        renderWeek() {
            this.week.textContent = `Kalenderwoche ab ${new Date(`${this.start}T12:00:00`).toLocaleDateString('de-DE')}`;
        }

        renderPeople() {
            const query = this.search.value.trim().toLocaleLowerCase('de-DE');
            this.people.replaceChildren(...this.employees.filter(employee => !query || employee.displayName.toLocaleLowerCase('de-DE').includes(query)).map(employee => {
                const label = document.createElement('label');
                const input = document.createElement('input');
                input.type = 'checkbox'; input.value = employee.uid; input.checked = this.selected.has(employee.uid);
                input.addEventListener('change', () => {
                    input.checked ? this.selected.add(employee.uid) : this.selected.delete(employee.uid);
                    this.results.replaceChildren();
                });
                label.append(input, document.createTextNode(` ${employee.displayName}`));
                return label;
            }));
        }

        async submit(event) {
            event.preventDefault();
            await this.searchWeek();
        }

        async searchWeek() {
            const employeeUids = [...this.selected];
            const durationMinutes = Number(this.duration.value);
            if (employeeUids.length < 2) {
                this.results.textContent = 'Bitte mindestens zwei Personen auswählen.';
                return;
            }
            if (!Number.isInteger(durationMinutes) || durationMinutes < 15 || durationMinutes > 480) {
                this.results.textContent = 'Bitte eine Dauer zwischen 15 und 480 Minuten auswählen.';
                return;
            }
            try {
                const response = await this.repository.meetingGaps(this.start, employeeUids, durationMinutes);
                this.renderResults(response.gaps || [], Boolean(response.canBlockAll));
            } catch (error) {
                this.onError(error);
            }
        }

        renderResults(gaps, canBlockAll) {
            if (!gaps.length) {
                this.renderNoResults();
                return;
            }
            const heading = document.createElement('h3'); heading.textContent = 'Passende Lücken';
            const list = document.createElement('ul');
            list.className = 'adc-meeting-gap-list';
            for (const gap of gaps) {
                const start = new Date(gap.start);
                const end = new Date(start.getTime() + Number(this.duration.value) * 60000);
                const item = document.createElement('li');
                const label = document.createElement('span');
                label.textContent = `${start.toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit' })}, ${this.time(start)}–${this.time(end)}`;
                item.append(label);
                if (canBlockAll) {
                    const block = document.createElement('button');
                    block.type = 'button'; block.textContent = 'Für alle blocken';
                    block.addEventListener('click', () => this.block(start, end, block));
                    item.append(block);
                }
                list.append(item);
            }
            const nodes = [heading, list];
            if (!canBlockAll) {
                const note = document.createElement('p');
                note.textContent = 'Direktes Blockieren ist nur möglich, wenn du alle ausgewählten Kalender bearbeiten darfst.';
                nodes.push(note);
            }
            this.results.replaceChildren(...nodes);
        }

        renderNoResults() {
            const message = document.createElement('p');
            message.textContent = 'In dieser Kalenderwoche wurde keine passende gemeinsame Lücke gefunden.';
            const actions = document.createElement('div');
            actions.className = 'adc-meeting-result-actions';
            const nextWeek = document.createElement('button');
            nextWeek.type = 'button'; nextWeek.textContent = 'In der nächsten Woche suchen';
            nextWeek.addEventListener('click', async () => {
                const date = new Date(`${this.start}T12:00:00`); date.setDate(date.getDate() + 7);
                this.start = CalendarDate.isoDay(date); this.renderWeek(); await this.searchWeek();
            });
            actions.append(nextWeek);
            for (const uid of this.selected) {
                const employee = this.employees.find(item => item.uid === uid);
                if (!employee) continue;
                const remove = document.createElement('button');
                remove.type = 'button'; remove.textContent = `${employee.displayName} abwählen`;
                remove.addEventListener('click', async () => {
                    this.selected.delete(uid); this.renderPeople();
                    if (this.selected.size >= 2) await this.searchWeek();
                    else this.results.textContent = 'Bitte mindestens zwei Personen auswählen.';
                });
                actions.append(remove);
            }
            this.results.replaceChildren(message, actions);
        }

        async block(start, end, button) {
            const title = this.title.value.trim();
            if (!title) {
                this.title.setCustomValidity('Bitte einen Titel für die Blockierung eingeben.');
                this.title.reportValidity();
                return;
            }
            this.title.setCustomValidity('');
            button.disabled = true;
            try {
                await this.repository.blockMeeting(start.toISOString(), end.toISOString(), [...this.selected], title);
                this.results.textContent = 'Der Termin wurde für alle ausgewählten Personen blockiert.';
                await this.onBlocked();
            } catch (error) {
                button.disabled = false;
                this.onError(error);
            }
        }

        time(date) { return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' }); }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.MeetingFinder = MeetingFinder;
})();
