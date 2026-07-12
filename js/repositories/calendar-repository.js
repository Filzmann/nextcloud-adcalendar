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

        settings() {
            return this.request('/api/settings');
        }

        saveSettings(peerEditing) {
            return this.request('/api/settings', { method: 'PUT', body: JSON.stringify({ peerEditing }) });
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.repositories = window.AdCalendar.repositories || {};
    window.AdCalendar.repositories.CalendarRepository = CalendarRepository;
})();
