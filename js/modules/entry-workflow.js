(function() {
    'use strict';

    /**
     * Zweck: Koordiniert Anlegen, Bearbeiten und Löschen von Kalender- und gemeinsamen Meetingeinträgen.
     * Zusammenspiel: CalendarCell liefert Aktionen, EntryDialog erfasst Daten, CalendarRepository persistiert und main.js lädt anschließend neu.
     * Vertrag: canManage steuert nur die Bedienoberfläche. Repository-Endpunkte und Server-Policies bleiben die maßgebliche Berechtigungsgrenze.
     */
    class EntryWorkflow {
        constructor(options) {
            this.repository = options.repository;
            this.state = options.state;
            this.dialog = options.dialog;
            this.show = options.show;
            this.reload = options.reload;
            options.body.addEventListener('click', event => this.handleClick(event));
        }

        async save(data) {
            try {
                const existing = this.state.data.entries.find(entry => entry.id === Number(data.id));
                if (existing?.meetingUid) {
                    if (!existing.canManageMeeting) throw new Error('Das gemeinsame Meeting darf nur bearbeitet werden, wenn alle beteiligten Kalender bearbeitet werden dürfen.');
                    await this.repository.updateMeeting(existing.meetingUid, data.start, data.end, data.title);
                } else {
                    await this.repository.save({
                        employeeUid: data.employeeUid,
                        type: data.type,
                        start: data.start,
                        end: data.end,
                        title: data.title,
                    }, data.id);
                }
                this.dialog.close();
                this.show(existing?.meetingUid ? 'Meeting für alle Beteiligten gespeichert.' : 'Eintrag gespeichert.');
                await this.reload();
            } catch (error) {
                this.show(error, true);
            }
        }

        async remove(entry) {
            if (entry.meetingUid) {
                await this.removeMeeting(entry);
                return;
            }
            let mode = '';
            if (entry.type === 'shift') {
                try {
                    await this.repository.remove(entry.id);
                    this.show('Dienst gelöscht.');
                    await this.reload();
                    return;
                } catch (error) {
                    if (error.status !== 409 || !error.data?.confirmationRequired) {
                        this.show(error, true);
                        return;
                    }
                    mode = await this.deletionChoice(error.data.children.length);
                }
                if (mode === null) return;
            } else if (!window.confirm('Termin wirklich löschen?')) {
                return;
            }
            try {
                await this.repository.remove(entry.id, mode);
                this.show(mode === 'detach' ? 'Dienst gelöscht; Termine sind jetzt Sperrtermine.' : 'Eintrag gelöscht.');
                await this.reload();
            } catch (error) {
                this.show(error, true);
            }
        }

        async removeMeeting(entry) {
            if (!entry.canManageMeeting) {
                this.show('Das gemeinsame Meeting darf nur gelöscht werden, wenn alle beteiligten Kalender bearbeitet werden dürfen.', true);
                return;
            }
            if (!window.confirm('Meeting wirklich für alle Beteiligten löschen?')) return;
            try {
                await this.repository.removeMeeting(entry.meetingUid);
                this.show('Meeting für alle Beteiligten gelöscht.');
                await this.reload();
            } catch (error) {
                this.show(error, true);
            }
        }

        handleClick(event) {
            const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
            if (!button) return;
            const cell = button.closest('td[data-employee-uid][data-day]');
            if (!cell) return;
            const employee = this.state.data.employees.find(item => item.uid === cell.dataset.employeeUid);
            if (!employee?.canManage) return;
            if (button.dataset.action === 'add-entry') {
                this.dialog.open({ employee, day: new Date(`${cell.dataset.day}T12:00:00`), type: button.dataset.entryType });
                return;
            }
            const entry = this.state.data.entries.find(item => item.id === Number(button.dataset.entryId));
            if (!entry) return;
            if (button.dataset.action === 'edit-entry') this.dialog.open({ employee, day: new Date(entry.start), type: entry.type, entry });
            if (button.dataset.action === 'delete-entry') void this.remove(entry);
        }

        deletionChoice(count) {
            return new Promise(resolve => {
                const dialog = document.createElement('dialog');
                dialog.className = 'adc-dialog adc-delete-dialog';
                dialog.setAttribute('aria-labelledby', 'adc-delete-title');
                let settled = false;
                const finish = value => {
                    if (settled) return;
                    settled = true;
                    dialog.close();
                    dialog.remove();
                    resolve(value);
                };
                const title = this.node('h2', 'Dienst mit Terminen löschen');
                title.id = 'adc-delete-title';
                dialog.append(title, this.node('p', `Der Dienst enthält ${count} Termin(e). Was soll damit geschehen?`));
                [['delete', 'Dienst und Termine löschen'], ['detach', 'Nur Dienst löschen; Termine als Sperrtermine behalten'], [null, 'Abbrechen']]
                    .forEach(([value, label]) => {
                        const button = this.node('button', label);
                        button.type = 'button';
                        button.addEventListener('click', () => finish(value));
                        dialog.append(button);
                    });
                dialog.addEventListener('cancel', event => { event.preventDefault(); finish(null); });
                document.body.append(dialog);
                dialog.showModal();
            });
        }

        node(tag, value) {
            const result = document.createElement(tag);
            result.textContent = value;
            return result;
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.modules = window.AdCalendar.modules || {};
    window.AdCalendar.modules.EntryWorkflow = EntryWorkflow;
})();
