(function() {
    'use strict';

    /**
     * Zweck: Steuert Kalenderwoche und Orientierung der Wochenmatrix als zusammengehörige Navigation.
     * Zusammenspiel: CalendarState persistiert die gewählte Ansicht; main.js lädt nach einem Wochenwechsel neue Daten.
     * Vertrag: Jede Navigationsaktion aktualisiert zuerst Zustand, URL und Beschriftung und ruft danach den passenden Callback auf.
     */
    class WeekNavigation {
        constructor(options) {
            this.state = options.state;
            this.onWeekChange = options.onWeekChange;
            this.onViewChange = options.onViewChange;
            this.label = document.getElementById('adc-week-label');
            this.weekNumber = document.getElementById('adc-week-number');
            this.toggleView = document.getElementById('adc-toggle-view');
            document.getElementById('adc-previous-week').addEventListener('click', () => this.move(-7));
            document.getElementById('adc-next-week').addEventListener('click', () => this.move(7));
            this.weekNumber.addEventListener('change', event => this.select(event.target.value));
            this.toggleView.addEventListener('click', () => this.toggle());
        }

        render() {
            const sunday = new Date(this.state.monday);
            sunday.setDate(sunday.getDate() + 6);
            this.label.textContent = `${this.state.monday.toLocaleDateString('de-DE')} – ${sunday.toLocaleDateString('de-DE')}`;
            this.weekNumber.value = this.isoWeekValue(this.state.monday);
            this.toggleView.textContent = this.state.vertical ? 'Tage als Zeilen' : 'Personen als Zeilen';
            this.toggleView.setAttribute('aria-pressed', String(!this.state.vertical));
        }

        move(days) {
            this.state.monday.setDate(this.state.monday.getDate() + days);
            this.changed(this.onWeekChange);
        }

        select(value) {
            if (!value) return;
            const [year, week] = value.split('-W').map(Number);
            if (!year || !week) return;
            const januaryFourth = new Date(year, 0, 4);
            this.state.monday = this.startOfWeek(januaryFourth);
            this.state.monday.setDate(this.state.monday.getDate() + (week - 1) * 7);
            this.changed(this.onWeekChange);
        }

        toggle() {
            this.state.vertical = !this.state.vertical;
            this.changed(this.onViewChange);
        }

        changed(callback) {
            this.state.persist();
            this.render();
            callback();
        }

        startOfWeek(value) {
            const result = new Date(value);
            const weekday = result.getDay() || 7;
            result.setDate(result.getDate() - weekday + 1);
            result.setHours(0, 0, 0, 0);
            return result;
        }

        isoWeekValue(date) {
            const value = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
            const day = value.getUTCDay() || 7;
            value.setUTCDate(value.getUTCDate() + 4 - day);
            const yearStart = new Date(Date.UTC(value.getUTCFullYear(), 0, 1));
            const week = Math.ceil((((value - yearStart) / 86400000) + 1) / 7);
            return `${value.getUTCFullYear()}-W${String(week).padStart(2, '0')}`;
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.WeekNavigation = WeekNavigation;
})();
