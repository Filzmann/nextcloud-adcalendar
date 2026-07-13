(function() {
    'use strict';

    const esc = window.LocalBase.ui.esc;

    /**
     * Zweck: Bearbeitet Gruppen-IDs, Anzeigenamen, Bereiche, Teamansichten und Hierarchiekanten der gemeinsamen AD-Organisation.
     * Zusammenspiel: main.js lädt/speichert über CalendarRepository; Organization hydriert denselben Payload für die Kalenderansicht.
     * Vertrag: Fachliche Schlüssel sind unveränderlich; frei änderbare Referenzen werden serverseitig vollständig validiert.
     */
    class OrganizationSettings {
        constructor(options) {
            this.container = options.container;
            this.form = options.form;
            this.onSave = options.onSave;
            this.definition = null;
            this.form.addEventListener('submit', event => {
                event.preventDefault();
                if (this.definition) this.onSave(this.collect());
            });
            this.container.addEventListener('click', event => {
                const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
                if (!button) return;
                if (button.dataset.action === 'remove-organization-team') button.closest('tr')?.remove();
                if (button.dataset.action === 'add-organization-team') this.addOrganizationTeam();
            });
        }

        set(definition) {
            this.definition = definition;
            this.render();
        }

        render() {
            const data = this.definition.toArray();
            const roles = Object.entries(data.roles).sort(([, a], [, b]) => Number(a.sortOrder) - Number(b.sortOrder));
            const areas = Object.entries(data.areas).sort(([, a], [, b]) => Number(a.sortOrder) - Number(b.sortOrder));
            this.container.innerHTML = `
                <fieldset class="adc-organization-general"><legend>Allgemein</legend>
                    <label>Präfix der Assistenzteams <input data-organization-field="teamGroupPrefix" value="${esc(data.teamGroupPrefix)}" required></label>
                    <label>Anzeigename der Assistenzteams <input data-organization-field="teamLabelPrefix" value="${esc(data.teamLabelPrefix)}" required></label>
                    <label>Maximale Kürzellänge <input data-organization-field="teamCodeMaxLength" type="number" min="1" max="64" value="${esc(data.teamCodeMaxLength)}" required></label>
                    <label>Titel des Leitungsblocks <input data-organization-field="staffBlockLabel" value="${esc(data.staffBlockLabel)}" required></label>
                </fieldset>
                <div class="adc-table-wrap"><table class="adc-organization-table"><caption>Fachrollen und Nextcloud-Gruppen</caption><thead><tr><th>Rolle</th><th>Gruppen-ID</th><th>Anzeigename</th><th>Kalender</th><th>Bereich</th><th>Leitung je Bereich</th><th>Peer-Recht</th><th>Leitungsblock</th><th>Reihenfolge</th></tr></thead><tbody>
                    ${roles.map(([key, role]) => `<tr data-role-key="${esc(key)}"><th scope="row"><code>${esc(key)}</code></th><td><input data-field="groupId" value="${esc(role.groupId)}" aria-label="Gruppen-ID ${esc(role.label)}" required></td><td><input data-field="label" value="${esc(role.label)}" aria-label="Anzeigename ${esc(key)}" required></td><td><input data-field="calendarVisible" type="checkbox" ${role.calendarVisible ? 'checked' : ''} aria-label="${esc(role.label)} im Kalender sichtbar"></td><td><input data-field="areaScoped" type="checkbox" ${role.areaScoped ? 'checked' : ''} aria-label="${esc(role.label)} ist bereichsgebunden"></td><td><input data-field="managementAreaScoped" type="checkbox" ${role.managementAreaScoped ? 'checked' : ''} aria-label="Leitungsrecht von ${esc(role.label)} ist bereichsgebunden"></td><td><input data-field="peerEnabled" type="checkbox" ${role.peerEnabled ? 'checked' : ''} aria-label="Peer-Recht für ${esc(role.label)}"></td><td><input data-field="staffBlock" type="checkbox" ${role.staffBlock ? 'checked' : ''} aria-label="${esc(role.label)} im Leitungsblock"></td><td><input data-field="sortOrder" type="number" value="${esc(role.sortOrder)}" aria-label="Reihenfolge ${esc(role.label)}"></td></tr>`).join('')}
                </tbody></table></div>
                <div class="adc-table-wrap"><table class="adc-organization-table"><caption>Bürobereiche</caption><thead><tr><th>Bereich</th><th>Gruppen-ID</th><th>Anzeigename</th><th>Reihenfolge</th></tr></thead><tbody>
                    ${areas.map(([key, area]) => `<tr data-area-key="${esc(key)}"><th scope="row"><code>${esc(key)}</code></th><td><input data-field="groupId" value="${esc(area.groupId)}" aria-label="Gruppen-ID ${esc(area.label)}" required></td><td><input data-field="label" value="${esc(area.label)}" aria-label="Anzeigename ${esc(key)}" required></td><td><input data-field="sortOrder" type="number" value="${esc(area.sortOrder)}" aria-label="Reihenfolge ${esc(area.label)}"></td></tr>`).join('')}
                </tbody></table></div>
                <fieldset class="adc-hierarchy-settings"><legend>Direkte Hierarchie</legend>
                    ${roles.map(([key, role]) => `<label data-manager-key="${esc(key)}"><span>${esc(role.label)} führt</span><select multiple size="4" aria-label="Direkt unterstellte Rollen von ${esc(role.label)}">${roles.filter(([target]) => target !== key).map(([target, targetRole]) => `<option value="${esc(target)}" ${(data.hierarchy[key] || []).includes(target) ? 'selected' : ''}>${esc(targetRole.label)}</option>`).join('')}</select></label>`).join('')}
                </fieldset>
                <div class="adc-table-wrap"><table class="adc-organization-table"><caption>Teamansichten im Urlaubsplaner</caption><thead><tr><th>ID</th><th>Anzeigename</th><th>Rollen</th><th>Bereiche</th><th>Reihenfolge</th><th>Aktion</th></tr></thead><tbody data-organization-teams>
                    ${data.organizationTeams.map(team => this.teamRow(team)).join('')}
                </tbody></table></div>
                <button type="button" data-action="add-organization-team">Urlaubsansicht hinzufügen</button>`;
        }

        collect() {
            const data = this.definition.toArray();
            data.teamGroupPrefix = this.container.querySelector('[data-organization-field="teamGroupPrefix"]').value.trim();
            data.teamLabelPrefix = this.container.querySelector('[data-organization-field="teamLabelPrefix"]').value.trim();
            data.teamCodeMaxLength = Number(this.container.querySelector('[data-organization-field="teamCodeMaxLength"]').value);
            data.staffBlockLabel = this.container.querySelector('[data-organization-field="staffBlockLabel"]').value.trim();
            this.container.querySelectorAll('[data-role-key]').forEach(row => {
                const role = data.roles[row.dataset.roleKey];
                for (const field of ['groupId', 'label']) role[field] = row.querySelector(`[data-field="${field}"]`).value.trim();
                for (const field of ['calendarVisible', 'areaScoped', 'managementAreaScoped', 'peerEnabled', 'staffBlock']) role[field] = row.querySelector(`[data-field="${field}"]`).checked;
                role.sortOrder = Number(row.querySelector('[data-field="sortOrder"]').value);
            });
            this.container.querySelectorAll('[data-area-key]').forEach(row => {
                const area = data.areas[row.dataset.areaKey];
                for (const field of ['groupId', 'label']) area[field] = row.querySelector(`[data-field="${field}"]`).value.trim();
                area.sortOrder = Number(row.querySelector('[data-field="sortOrder"]').value);
            });
            data.hierarchy = {};
            this.container.querySelectorAll('[data-manager-key]').forEach(label => {
                const targets = [...label.querySelector('select').selectedOptions].map(option => option.value);
                if (targets.length) data.hierarchy[label.dataset.managerKey] = targets;
            });
            data.organizationTeams = [...this.container.querySelectorAll('[data-organization-team]')].map(row => ({
                id: row.querySelector('[data-field="id"]').value.trim(),
                label: row.querySelector('[data-field="label"]').value.trim(),
                roles: this.list(row.querySelector('[data-field="roles"]').value),
                areas: this.list(row.querySelector('[data-field="areas"]').value),
                sortOrder: Number(row.querySelector('[data-field="sortOrder"]').value),
            }));
            return data;
        }

        addOrganizationTeam() {
            const rows = [...this.container.querySelectorAll('[data-organization-team]')];
            const ids = new Set(rows.map(row => row.querySelector('[data-field="id"]').value));
            let number = rows.length + 1;
            while (ids.has(`view-${number}`)) number += 1;
            const order = Math.max(0, ...rows.map(row => Number(row.querySelector('[data-field="sortOrder"]').value) || 0)) + 10;
            this.container.querySelector('[data-organization-teams]').insertAdjacentHTML('beforeend', this.teamRow({ id: `view-${number}`, label: 'Neue Urlaubsansicht', roles: [], areas: [], sortOrder: order }));
        }

        teamRow(team) {
            return `<tr data-organization-team><td><input data-field="id" value="${esc(team.id)}" aria-label="ID der Urlaubsansicht" required pattern="[a-z][a-z0-9_-]*"></td><td><input data-field="label" value="${esc(team.label)}" aria-label="Anzeigename der Urlaubsansicht ${esc(team.id)}" required></td><td><input data-field="roles" value="${esc(team.roles.join(', '))}" aria-label="Rollenschlüssel der Urlaubsansicht ${esc(team.id)}" required></td><td><input data-field="areas" value="${esc(team.areas.join(', '))}" aria-label="Bereichsschlüssel der Urlaubsansicht ${esc(team.id)}"></td><td><input data-field="sortOrder" type="number" value="${esc(team.sortOrder)}" aria-label="Reihenfolge der Urlaubsansicht ${esc(team.id)}"></td><td><button type="button" data-action="remove-organization-team" aria-label="Urlaubsansicht ${esc(team.label)} entfernen">Entfernen</button></td></tr>`;
        }

        list(value) { return [...new Set(value.split(',').map(item => item.trim()).filter(Boolean))]; }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.OrganizationSettings = OrganizationSettings;
})();
