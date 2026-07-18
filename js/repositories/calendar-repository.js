(function() {
    'use strict';

    const BaseRepository = window.LocalBase.repositories.Repository;

    /** Zweck: Kapselt alle AD-Kalender-API-Pfade hinter dem gemeinsamen LocalBase-Client. */
    class CalendarRepository extends BaseRepository {
        week(start) {
            return this.request(`/api/week?start=${this.encode(start)}`);
        }

        save(entry, id = null) {
            return this.request(id == null ? '/api/entries' : `/api/entries/${this.encode(id)}`, {
                method: id == null ? 'POST' : 'PUT',
                body: JSON.stringify(entry),
            });
        }

        remove(id, childMode = '') {
            return this.request(`/api/entries/${this.encode(id)}`, {
                method: 'DELETE',
                body: JSON.stringify({ childMode }),
            });
        }

        savePreferences(filters) {
            return this.request('/api/preferences', { method: 'PUT', body: JSON.stringify({ filters }) });
        }

        saveShiftDefaults(shiftDefaults) {
            return this.request('/api/preferences/shifts', { method: 'PUT', body: JSON.stringify({ shiftDefaults }) });
        }

        saveCalendarSync(enabled) {
            return this.request('/api/preferences/calendar-sync', { method: 'PUT', body: JSON.stringify({ enabled }) });
        }

        meetingGaps(start, employeeUids, durationMinutes) {
            return this.request('/api/meeting-gaps', {
                method: 'POST',
                body: JSON.stringify({ start, employeeUids, durationMinutes }),
            });
        }

        blockMeeting(start, end, employeeUids, title) {
            return this.request('/api/meetings', {
                method: 'POST',
                body: JSON.stringify({ start, end, employeeUids, title }),
            });
        }

        updateMeeting(meetingUid, start, end, title) {
            return this.request(`/api/meetings/${this.encode(meetingUid)}`, {
                method: 'PUT',
                body: JSON.stringify({ start, end, title }),
            });
        }

        removeMeeting(meetingUid) {
            return this.request(`/api/meetings/${this.encode(meetingUid)}`, { method: 'DELETE' });
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.repositories = window.AdCalendar.repositories || {};
    window.AdCalendar.repositories.CalendarRepository = CalendarRepository;
})();
