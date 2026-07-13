(function() {
    'use strict';

    /** Zweck: Stellt die app-weit identischen Berechnungen für lokale Kalendertage und ISO-Wochen zentral bereit. */
    class CalendarDate {
        static isoDay(value) {
            const year = value.getFullYear();
            const month = String(value.getMonth() + 1).padStart(2, '0');
            const day = String(value.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        static startOfWeek(value) {
            const result = new Date(value);
            const weekday = result.getDay() || 7;
            result.setDate(result.getDate() - weekday + 1);
            result.setHours(0, 0, 0, 0);
            return result;
        }

        static isoWeekValue(date) {
            const value = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
            const day = value.getUTCDay() || 7;
            value.setUTCDate(value.getUTCDate() + 4 - day);
            const yearStart = new Date(Date.UTC(value.getUTCFullYear(), 0, 1));
            const week = Math.ceil((((value - yearStart) / 86400000) + 1) / 7);
            return `${value.getUTCFullYear()}-W${String(week).padStart(2, '0')}`;
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.modules = window.AdCalendar.modules || {};
    window.AdCalendar.modules.CalendarDate = CalendarDate;
})();
