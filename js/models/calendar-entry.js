(function() {
    'use strict';

    const BaseModel = window.LocalBase.models.Model;

    /**
     * Zweck: Hydriert den serverseitigen CalendarEntry-Vertrag fuer Komponenten und Workflows.
     * Spiegelung: PHP: OCA\AdCalendar\Model\CalendarEntry::toArray().
     */
    class CalendarEntry extends BaseModel {
        constructor(data = {}) {
            super();
            this.id = data.id == null ? null : Number(data.id);
            this.employeeUid = String(data.employeeUid || '');
            this.start = String(data.start || '');
            this.end = String(data.end || '');
            this.type = String(data.type || '');
            this.title = String(data.title || '');
            this.parentEntryId = data.parentEntryId == null ? null : Number(data.parentEntryId);
            this.isBlocked = Boolean(data.isBlocked);
        }

        toArray() {
            return {
                id: this.id,
                employeeUid: this.employeeUid,
                start: this.start,
                end: this.end,
                type: this.type,
                title: this.title,
                parentEntryId: this.parentEntryId,
                isBlocked: this.isBlocked,
            };
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.models = window.AdCalendar.models || {};
    window.AdCalendar.models.CalendarEntry = CalendarEntry;
})();
