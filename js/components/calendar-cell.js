(function() {
    'use strict';

    const { esc } = window.LocalBase.ui;

    /**
     * Zweck: Rendert einen kompakten Mitarbeiter-Tag und ordnet Termine sichtbar ihrem Dienst zu.
     * Zusammenspiel: main.js liefert Tagesdaten und bindet die delegierten data-action-Ereignisse.
     */
    class CalendarCell {
        render(entries, employee, absences = []) {
            const shifts = entries.filter(entry => entry.type === 'shift');
            const standalone = entries.filter(entry => entry.type === 'appointment' && entry.parentEntryId === null);
            const approved = absences.some(absence => absence.blocks);
            const actions = employee.canManage && !approved ? `
                <div class="adc-cell-actions" aria-label="Eintrag anlegen">
                    <button type="button" class="adc-quick-add adc-icon-button icon-add" data-action="add-entry" data-entry-type="shift" data-tooltip="Dienst anlegen" aria-label="Dienst anlegen" title="Dienst anlegen"></button>
                    <button type="button" class="adc-quick-add adc-icon-button icon-calendar-dark" data-action="add-entry" data-entry-type="appointment" data-tooltip="Termin anlegen" aria-label="Termin anlegen" title="Termin anlegen"></button>
                </div>` : '';

            const markers = absences.map(absence => `<div class="adc-absence adc-absence--${esc(absence.status)}" title="${absence.blocks ? 'Genehmigter Urlaub – Einträge sind gesperrt' : 'Geplanter Urlaub – Hinweis ohne Sperre'}"><strong>${esc(absence.marker)}</strong> ${absence.blocks ? 'Genehmigter Urlaub' : 'Urlaub geplant'}</div>`).join('');
            return `${markers}${actions}<div class="adc-cell-entries">${shifts.map(shift => this.shift(shift, entries, employee.canManage && !approved)).join('')}${standalone.map(entry => this.entry(entry, 'blocked', employee.canManage && !approved)).join('')}</div>`;
        }

        shift(shift, entries, canManage) {
            const children = entries.filter(entry => entry.type === 'appointment' && entry.parentEntryId === shift.id);
            return `<article class="adc-entry adc-entry--shift" data-entry-id="${esc(shift.id)}">
                ${this.header(shift, 'Dienst', canManage)}
                ${children.length ? `<div class="adc-entry__children" aria-label="Termine innerhalb des Dienstes">${children.map(entry => this.entry(entry, 'appointment', canManage)).join('')}</div>` : ''}
            </article>`;
        }

        entry(entry, kind, canManage) {
            const label = kind === 'blocked' ? 'Sperrtermin' : 'Termin';
            return `<article class="adc-entry adc-entry--${kind}" data-entry-id="${esc(entry.id)}">${this.header(entry, label, canManage)}</article>`;
        }

        header(entry, label, canManage) {
            const title = entry.title ? `<span class="adc-entry__title">${esc(entry.title)}</span>` : '';
            const controls = canManage ? `<span class="adc-entry__actions">
                <button type="button" class="adc-icon-button icon-rename" data-action="edit-entry" data-entry-id="${esc(entry.id)}" aria-label="${esc(label)} bearbeiten" title="Bearbeiten"></button>
                <button type="button" class="adc-icon-button icon-delete" data-action="delete-entry" data-entry-id="${esc(entry.id)}" aria-label="${esc(label)} löschen" title="Löschen"></button>
            </span>` : '';
            return `<header class="adc-entry__header"><span><strong>${esc(label)}</strong> ${esc(this.time(entry.start))}–${esc(this.time(entry.end))}</span>${controls}</header>${title}`;
        }

        time(value) {
            return new Date(value).toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.CalendarCell = CalendarCell;
})();
