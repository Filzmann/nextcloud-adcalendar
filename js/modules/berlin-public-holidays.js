(function () {
    'use strict';

    /**
     * Zweck: Bestimmt die gesetzlichen Berliner Feiertage ohne Laufzeitabhängigkeit zu einem Fremddienst.
     * Quelle: § 1 Feiertagsgesetz Berlin; bewegliche Feiertage werden vom Ostersonntag abgeleitet.
     */
    class BerlinPublicHolidays {
        constructor() {
            this.cache = new Map();
        }

        name(date) {
            return this.forYear(date.getFullYear()).get(this.isoDay(date)) || '';
        }

        forYear(year) {
            if (this.cache.has(year)) return this.cache.get(year);
            const holidays = new Map([
                [`${year}-01-01`, 'Neujahr'],
                [`${year}-03-08`, 'Internationaler Frauentag'],
                [`${year}-05-01`, 'Tag der Arbeit'],
                [`${year}-10-03`, 'Tag der Deutschen Einheit'],
                [`${year}-12-25`, '1. Weihnachtsfeiertag'],
                [`${year}-12-26`, '2. Weihnachtsfeiertag'],
            ]);
            const easter = this.easterSunday(year);
            for (const [offset, name] of [
                [-2, 'Karfreitag'],
                [1, 'Ostermontag'],
                [39, 'Christi Himmelfahrt'],
                [50, 'Pfingstmontag'],
            ]) holidays.set(this.isoDay(this.addDays(easter, offset)), name);
            if (year === 2028) holidays.set('2028-06-17', '75. Jahrestag des Aufstandes vom 17. Juni 1953');
            this.cache.set(year, holidays);
            return holidays;
        }

        easterSunday(year) {
            const a = year % 19;
            const b = Math.floor(year / 100);
            const c = year % 100;
            const d = Math.floor(b / 4);
            const e = b % 4;
            const f = Math.floor((b + 8) / 25);
            const g = Math.floor((b - f + 1) / 3);
            const h = (19 * a + b - d - g + 15) % 30;
            const i = Math.floor(c / 4);
            const k = c % 4;
            const l = (32 + 2 * e + 2 * i - h - k) % 7;
            const m = Math.floor((a + 11 * h + 22 * l) / 451);
            const month = Math.floor((h + l - 7 * m + 114) / 31);
            const day = ((h + l - 7 * m + 114) % 31) + 1;
            return new Date(year, month - 1, day, 12);
        }

        addDays(date, offset) {
            const result = new Date(date);
            result.setDate(result.getDate() + offset);
            return result;
        }

        isoDay(date) {
            const pad = value => String(value).padStart(2, '0');
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.modules = window.AdCalendar.modules || {};
    window.AdCalendar.modules.BerlinPublicHolidays = BerlinPublicHolidays;
}());
