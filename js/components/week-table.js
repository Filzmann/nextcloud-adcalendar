(function() {
    'use strict';
    const CalendarDate = window.AdCalendar.modules.CalendarDate;

    /**
     * Zweck: Rendert beide Wochenmatrix-Ansichten inklusive Fachgruppen- und Hierarchiesortierung.
     * Zusammenspiel: main.js filtert Personen; CalendarCell rendert den Inhalt eines Mitarbeiter-Tags.
     */
    class WeekTable {
        constructor(options) {
            this.head = options.head;
            this.body = options.body;
            this.calendarCell = options.calendarCell;
            this.organization = options.organization;
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
                for (const day of this.days(state.monday)) row.append(this.cellFor(employee, day, state.data.entries, state.data.absences || []));
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
                for (const employee of employees) row.append(this.cellFor(employee, day, state.data.entries, state.data.absences || []));
                return row;
            });
            this.body.replaceChildren(...rows);
        }

        cellFor(employee, day, allEntries, allAbsences) {
            const cell = document.createElement('td');
            const dayEnd = new Date(day); dayEnd.setDate(dayEnd.getDate() + 1);
            const entries = allEntries.filter(entry => entry.employeeUid === employee.uid && new Date(entry.start) < dayEnd && new Date(entry.end) > day);
            const absences = allAbsences.filter(absence => absence.employeeUid === employee.uid && new Date(absence.start) < dayEnd && new Date(absence.end) > day);
            cell.dataset.employeeUid = employee.uid;
            cell.dataset.day = CalendarDate.isoDay(day);
            cell.innerHTML = this.calendarCell.render(entries, employee, absences);
            return cell;
        }

        clusterLabel(employee) {
            const organization = this.organization();
            const staffRoles = new Set(organization.staffRoleGroups());
            if (employee.roles.some(role => staffRoles.has(role))) return organization.staffBlockLabel;
            const roleNames = employee.roles.map(value => organization.roleLabel(value));
            const roles = roleNames.length > 1 ? `${roleNames[0]} (${roleNames.slice(1).join(', ')})` : roleNames[0] || 'Ohne Fachrolle';
            const areas = employee.areas.map(value => organization.areaLabel(value)).join(' / ');
            return areas ? `${roles} · ${areas}` : roles;
        }

        employeeOrder(a, b) {
            const clusterComparison = this.clusterLabel(a).localeCompare(this.clusterLabel(b), 'de');
            if (clusterComparison !== 0) return clusterComparison;
            const hierarchyComparison = this.staffRank(a) - this.staffRank(b);
            return hierarchyComparison || a.displayName.localeCompare(b.displayName, 'de');
        }

        staffRank(employee) {
            const ranks = employee.roles.map(role => this.organization().roleOrder(role));
            return ranks.length ? Math.min(...ranks) : Number.MAX_SAFE_INTEGER;
        }

        days(monday) { return Array.from({ length: 7 }, (_, offset) => { const day = new Date(monday); day.setDate(day.getDate() + offset); return day; }); }
        node(tag, value, className) { const result = document.createElement(tag); result.textContent = value; if (className) result.className = className; return result; }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.WeekTable = WeekTable;
})();
