(function() {
    'use strict';

    const { esc } = window.LocalBase.ui;

    /**
     * Zweck: Rendert einen kompakten Mitarbeiter-Tag und ordnet Termine sichtbar ihrem Dienst zu.
     * Zusammenspiel: main.js liefert Tagesdaten und bindet die delegierten data-action-Ereignisse.
     */
    class CalendarCell {
        render(entries, employee, absences = [], layout = null, day = null, timeline = null) {
            const shifts = entries.filter(entry => entry.type === 'shift');
            const standalone = entries.filter(entry => entry.type === 'appointment' && entry.parentEntryId === null);
            const approved = absences.some(absence => absence.blocks);
            const actions = this.actions(employee.canManage && !approved);
            const markers = this.absenceMarkers(absences);
            const gridStyle = layout ? ` style="grid-template-rows:${layout.rows}"` : '';
            const manageable = employee.canManage && !approved;
            const shiftEntries = shifts
                .map(shift => this.shift(
                    shift,
                    entries,
                    manageable,
                    this.rowStyle(layout, shift, day, timeline),
                ))
                .join('');
            const blockedEntries = standalone
                .map(entry => this.entry(
                    entry,
                    'blocked',
                    manageable,
                    this.rowStyle(layout, entry, day, timeline),
                ))
                .join('');

            return `${markers}${actions}<div class="adc-cell-entries"${gridStyle}>${shiftEntries}${blockedEntries}</div>`;
        }

        actions(canManage) {
            const buttons = canManage ? `
                <button type="button" class="adc-quick-add adc-icon-button icon-add" data-action="add-entry" data-entry-type="shift" data-tooltip="Dienst anlegen" aria-label="Dienst anlegen" title="Dienst anlegen"></button>
                <button type="button" class="adc-quick-add adc-icon-button icon-calendar-dark" data-action="add-entry" data-entry-type="appointment" data-tooltip="Termin anlegen" aria-label="Termin anlegen" title="Termin anlegen"></button>` : '';

            return `<div class="adc-cell-actions" aria-label="Eintrag anlegen">${buttons}</div>`;
        }

        absenceMarkers(absences) {
            return absences.map(absence => {
                const label = absence.blocks ? 'Genehmigter Urlaub' : 'Urlaub geplant';
                const description = absence.blocks
                    ? 'Genehmigter Urlaub – Einträge sind gesperrt'
                    : 'Geplanter Urlaub – Hinweis ohne Sperre';

                return `<div class="adc-absence adc-absence--${esc(absence.status)}" title="${description}"><strong>${esc(absence.marker)}</strong> ${label}</div>`;
            }).join('');
        }

        shift(shift, entries, canManage, style = '') {
            const children = entries.filter(entry => entry.type === 'appointment' && entry.parentEntryId === shift.id);
            const childEntries = children.length
                ? `<div class="adc-entry__children" aria-label="Termine innerhalb des Dienstes">${children.map(entry => this.entry(
                    entry,
                    'appointment',
                    canManage && (!entry.meetingUid || entry.canManageMeeting !== false),
                )).join('')}</div>`
                : '';

            return `<article class="adc-entry adc-entry--shift" data-entry-id="${esc(shift.id)}"${style}>
                ${this.header(shift, 'Dienst', canManage)}
                ${childEntries}
            </article>`;
        }

        entry(entry, kind, canManage, style = '') {
            const label = kind === 'blocked' ? 'Sperrtermin' : 'Termin';
            const manageable = canManage && (!entry.meetingUid || entry.canManageMeeting !== false);
            return `<article class="adc-entry adc-entry--${kind}" data-entry-id="${esc(entry.id)}"${style}>${this.header(entry, label, manageable)}</article>`;
        }

        rowStyle(layout, entry, day, timeline) {
            if (!layout || !day || !timeline) return '';
            return ` style="grid-row:${timeline.gridRow(layout, entry, day)}"`;
        }

        header(entry, label, canManage) {
            const title = entry.title ? `<span class="adc-entry__title">${esc(entry.title)}</span>` : '';
            const blockedMarker = label === 'Sperrtermin' ? '<span class="adc-entry__blocked-marker" aria-hidden="true">🔒</span>' : '';
            const seriesMarker = entry.seriesUid ? '<span class="adc-entry__series-marker" title="Serientermin"><span aria-hidden="true">↻</span><span class="hidden-visually">Serientermin</span></span>' : '';
            const controls = canManage
                ? `<span class="adc-entry__actions">
                    <button type="button" class="adc-icon-button icon-rename" data-action="edit-entry" data-entry-id="${esc(entry.id)}" aria-label="${esc(label)} bearbeiten" title="Bearbeiten"></button>
                    <button type="button" class="adc-icon-button icon-delete" data-action="delete-entry" data-entry-id="${esc(entry.id)}" aria-label="${esc(label)} löschen" title="Löschen"></button>
                </span>`
                : '';

            return `<header class="adc-entry__header"><span>${blockedMarker}${seriesMarker}<strong>${esc(label)}</strong> ${esc(this.time(entry.start))}–${esc(this.time(entry.end))}</span>${controls}</header>${title}`;
        }

        time(value) {
            return new Date(value).toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.CalendarCell = CalendarCell;
})();
