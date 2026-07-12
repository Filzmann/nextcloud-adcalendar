(function() {
    'use strict';

    /**
     * Zweck: Rendert beide Wochenmatrix-Ansichten inklusive Fachgruppen- und Hierarchiesortierung.
     * Zusammenspiel: main.js filtert Personen; CalendarCell rendert den Inhalt eines Mitarbeiter-Tags.
     */
    class WeekTable {
        constructor(options) {
            this.head = options.head;
            this.body = options.body;
            this.calendarCell = options.calendarCell;
            this.leadershipStaffRoles = options.leadershipStaffRoles;
            this.leadershipStaffOrder = options.leadershipStaffOrder;
        }

        render(employees, state) {
            if (state.vertical) this.vertical(employees, state);
            else this.horizontal(employees, state);
        }

        vertical(employees, state) {
            const header = document.createElement('tr'); header.append(this.node('th', 'Mitarbeiter*in'));
            for (const day of this.days(state.monday)) header.append(this.node('th', day.toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit' })));
            this.head.replaceChildren(header);

            const rows = [];
            let previousCluster = null;
            for (const employee of employees.slice().sort((a, b) => this.employeeOrder(a, b))) {
                const cluster = this.clusterLabel(employee);
                if (!state.selected.size && cluster !== previousCluster) {
                    const groupRow = document.createElement('tr');
                    const groupCell = this.node('th', cluster, 'adc-group-heading');
                    groupCell.colSpan = 8; groupRow.append(groupCell); rows.push(groupRow); previousCluster = cluster;
                }
                const row = document.createElement('tr');
                const name = this.node('th', employee.displayName, state.selected.has(employee.uid) ? 'adc-selected' : '');
                name.scope = 'row'; row.append(name);
                for (const day of this.days(state.monday)) row.append(this.cellFor(employee, day, state.data.entries));
                rows.push(row);
            }
            this.body.replaceChildren(...rows);
        }

        horizontal(employees, state) {
            const header = document.createElement('tr'); header.append(this.node('th', 'Tag'));
            for (const employee of employees) header.append(this.node('th', employee.displayName, state.selected.has(employee.uid) ? 'adc-selected' : ''));
            this.head.replaceChildren(header);
            const rows = this.days(state.monday).map(day => {
                const row = document.createElement('tr');
                const label = this.node('th', day.toLocaleDateString('de-DE', { weekday: 'long', day: '2-digit', month: '2-digit' }));
                label.scope = 'row'; row.append(label);
                for (const employee of employees) row.append(this.cellFor(employee, day, state.data.entries));
                return row;
            });
            this.body.replaceChildren(...rows);
        }

        cellFor(employee, day, allEntries) {
            const cell = document.createElement('td');
            const dayEnd = new Date(day); dayEnd.setDate(dayEnd.getDate() + 1);
            const entries = allEntries.filter(entry => entry.employeeUid === employee.uid && new Date(entry.start) < dayEnd && new Date(entry.end) > day);
            cell.dataset.employeeUid = employee.uid;
            cell.dataset.day = this.isoDay(day);
            cell.innerHTML = this.calendarCell.render(entries, employee);
            return cell;
        }

        clusterLabel(employee) {
            if (employee.roles.some(role => this.leadershipStaffRoles.has(role))) return 'Geschäftsführung, PDL und Stabsstellen';
            const roles = employee.roles.map(value => value.replace('ad-', '').replace('Stab-', 'Stab ')).join(', ') || 'Ohne Fachrolle';
            const areas = employee.areas.map(value => value.replace('ad-Bereich-', '')).join(', ');
            return areas ? `${roles} · ${areas}` : roles;
        }

        employeeOrder(a, b) {
            const clusterComparison = this.clusterLabel(a).localeCompare(this.clusterLabel(b), 'de');
            if (clusterComparison !== 0) return clusterComparison;
            const hierarchyComparison = this.staffRank(a) - this.staffRank(b);
            return hierarchyComparison || a.displayName.localeCompare(b.displayName, 'de');
        }

        staffRank(employee) {
            const ranks = employee.roles.filter(role => this.leadershipStaffOrder.has(role)).map(role => this.leadershipStaffOrder.get(role));
            return ranks.length ? Math.min(...ranks) : Number.MAX_SAFE_INTEGER;
        }

        days(monday) { return Array.from({ length: 7 }, (_, offset) => { const day = new Date(monday); day.setDate(day.getDate() + offset); return day; }); }
        isoDay(value) { const year = value.getFullYear(); const month = String(value.getMonth() + 1).padStart(2, '0'); const day = String(value.getDate()).padStart(2, '0'); return `${year}-${month}-${day}`; }
        node(tag, value, className) { const result = document.createElement(tag); result.textContent = value; if (className) result.className = className; return result; }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.WeekTable = WeekTable;
})();
