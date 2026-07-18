(function() {
    'use strict';

    /**
     * Zweck: Rendert Rollen-, Bereichs- und Personenfilter und bindet deren Interaktionen.
     * Zusammenspiel: CalendarState hält den Filterzustand; main.js reagiert über onChange mit einer neuen Tabellenansicht.
     * Vertrag: Änderungen werden vor onChange im Zustand und in der URL persistiert. Der Reset entfernt nur explizit ausgewählte Personen.
     */
    class CalendarFilters {
        constructor(options) {
            this.state = options.state;
            this.organization = options.organization;
            this.leadershipStaffRoles = options.leadershipStaffRoles;
            this.onChange = options.onChange;
            this.roles = document.getElementById('adc-role-filters');
            this.areas = document.getElementById('adc-area-filters');
            this.search = document.getElementById('adc-person-search');
            this.searchResults = document.getElementById('adc-search-results');
            this.selectedPeople = document.getElementById('adc-selected-people');
            this.reset = document.getElementById('adc-reset-selection');
            this.search.addEventListener('input', event => this.renderSearch(event.target.value));
            this.reset.addEventListener('click', () => this.resetSelection());
        }

        render() {
            const employees = this.state.data?.employees || [];
            const organization = this.organization();
            const roles = [...new Set(employees.flatMap(employee => employee.roles))]
                .filter(role => !this.leadershipStaffRoles.has(role))
                .sort((a, b) => organization.roleOrder(a) - organization.roleOrder(b));
            const areas = [...new Set(employees.flatMap(employee => employee.areas))]
                .sort((a, b) => organization.areaLabel(a).localeCompare(organization.areaLabel(b), 'de'));
            this.renderCheckboxes(this.roles, roles, this.state.roles, value => organization.roleLabel(value));
            this.renderLeadershipStaffCheckbox();
            this.renderCheckboxes(this.areas, areas, this.state.areas, value => organization.areaLabel(value));
            this.renderSelected();
        }

        renderLeadershipStaffCheckbox() {
            const label = document.createElement('label');
            const input = document.createElement('input');
            const unfiltered = this.state.isUnfiltered();
            input.type = 'checkbox';
            input.checked = unfiltered || this.state.showLeadershipStaff;
            input.disabled = unfiltered;
            input.addEventListener('change', () => {
                this.state.showLeadershipStaff = input.checked;
                if (!input.checked) this.state.leadershipStaffOnly = false;
                if (!input.checked) for (const role of this.leadershipStaffRoles) this.state.roles.delete(role);
                this.changed(true);
            });
            label.append(input, document.createTextNode(` ${this.organization().staffBlockLabel} anzeigen`));
            this.roles.append(label);
        }

        renderCheckboxes(container, values, selected, labelFor) {
            container.replaceChildren(...values.map(value => {
                const label = document.createElement('label');
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.checked = selected.has(value);
                input.addEventListener('change', () => {
                    this.state.leadershipStaffOnly = false;
                    input.checked ? selected.add(value) : selected.delete(value);
                    this.changed(true);
                });
                label.append(input, document.createTextNode(` ${labelFor(value)}`));
                return label;
            }));
        }

        renderSelected() {
            const people = (this.state.data?.employees || []).filter(employee => this.state.selected.has(employee.uid));
            this.reset.hidden = people.length === 0;
            if (!people.length) {
                this.selectedPeople.replaceChildren(this.node('li', 'Keine explizite Auswahl – Gruppenfilter gelten.'));
                return;
            }
            this.selectedPeople.replaceChildren(...people.map(employee => {
                const item = this.node('li');
                const button = this.node('button', `${employee.displayName} entfernen`);
                button.type = 'button';
                button.addEventListener('click', () => {
                    this.state.selected.delete(employee.uid);
                    this.changed(true);
                });
                item.append(button);
                return item;
            }));
        }

        renderSearch(value) {
            const query = value.trim().toLocaleLowerCase('de-DE');
            const matches = query ? (this.state.data?.employees || [])
                .filter(employee => employee.displayName.toLocaleLowerCase('de-DE').includes(query) && !this.state.selected.has(employee.uid))
                .slice(0, 12) : [];
            this.searchResults.replaceChildren(...matches.map(employee => {
                const item = this.node('li');
                const button = this.node('button', `${employee.displayName} auswählen`);
                button.type = 'button';
                button.addEventListener('click', () => {
                    this.state.leadershipStaffOnly = false;
                    this.state.selected.add(employee.uid);
                    this.clearSearch();
                    this.changed(true);
                });
                item.append(button);
                return item;
            }));
        }

        resetSelection() {
            this.state.selected.clear();
            this.clearSearch();
            this.changed(true);
        }

        changed(renderSelection = false) {
            this.state.persist();
            if (renderSelection) this.render();
            this.onChange();
        }

        clearSearch() {
            this.search.value = '';
            this.searchResults.replaceChildren();
        }

        node(tag, value) {
            const result = document.createElement(tag);
            if (value !== undefined) result.textContent = value;
            return result;
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.CalendarFilters = CalendarFilters;
})();
