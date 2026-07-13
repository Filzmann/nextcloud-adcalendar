(function () {
    'use strict';
    const apiClient = new window.LocalBase.api.ApiClient({
        appId: 'adcalendar',
        errorMessage: (data, status) => data?.error || data?.message || `HTTP ${status}`,
    });
    const repository = new window.AdCalendar.repositories.CalendarRepository(apiClient);
    const notice = new window.LocalBase.ui.Notice('adc-notice', { baseClass: 'adc-notice', typeClassPrefix: 'adc-notice--' });
    const EntryModel = window.AdCalendar.models.CalendarEntry;
    const OrganizationModel = window.AdCalendar.models.Organization;
    const leadershipStaffRoles = new Set();
    let organization = new OrganizationModel({});
    const elements = Object.fromEntries(['week-label','calendar-body','calendar-head','notice','role-filters','area-filters','person-search','search-results','selected-people','filter-status','week-number','toggle-view','settings','settings-form','peer-settings','organization-form','organization-settings'].map(id => [id, document.getElementById(`adc-${id}`)]));
    const state = new window.AdCalendar.modules.CalendarState(leadershipStaffRoles).restore();
    const tabs = new window.AdCalendar.components.TabNavigation({
        calendarButton: document.getElementById('adc-tab-calendar'),
        settingsButton: document.getElementById('adc-tab-settings'),
        calendarPanel: document.getElementById('adc-calendar-view'),
        settingsPanel: document.getElementById('adc-settings-view'),
        onChange: tab => { state.activeTab = tab; state.persist(); },
    });
    tabs.show(state.activeTab, false);
    const calendarCell = new window.AdCalendar.components.CalendarCell();
    const weekTable = new window.AdCalendar.components.WeekTable({
        head: elements['calendar-head'], body: elements['calendar-body'], calendarCell,
        organization: () => organization,
    });
    const entryDialog = new window.AdCalendar.components.EntryDialog({
        entries: () => state.data?.entries || [],
        shiftDefaults: () => state.data?.shiftDefaults || {},
        onSubmit: saveEntry,
    });
    const meetingFinder = new window.AdCalendar.components.MeetingFinder({ repository, onError: error => show(error, true) });
    const shiftDefaults = new window.AdCalendar.components.ShiftDefaults({ onSave: saveShiftDefaults });
    const organizationSettings = new window.AdCalendar.components.OrganizationSettings({
        container: elements['organization-settings'], form: elements['organization-form'], onSave: saveOrganization,
    });

    function startOfWeek(value) { const result = new Date(value); const weekday = result.getDay() || 7; result.setDate(result.getDate() - weekday + 1); result.setHours(0, 0, 0, 0); return result; }
    function isoDay(value) { const year = value.getFullYear(); const month = String(value.getMonth() + 1).padStart(2, '0'); const day = String(value.getDate()).padStart(2, '0'); return `${year}-${month}-${day}`; }
    function node(tag, value, className) { const result = document.createElement(tag); if (value !== undefined) result.textContent = value; if (className) result.className = className; return result; }
    function show(message, error) { if (error) notice.error(message); else if (message) notice.success(message); else notice.clear(); }

    function renderFilters() {
        const roles = [...new Set(state.data.employees.flatMap(employee => employee.roles))].filter(role => !leadershipStaffRoles.has(role)).sort((a, b) => organization.roleOrder(a) - organization.roleOrder(b));
        const areas = [...new Set(state.data.employees.flatMap(employee => employee.areas))].sort((a, b) => organization.areaLabel(a).localeCompare(organization.areaLabel(b), 'de'));
        renderCheckboxes(elements['role-filters'], roles, state.roles);
        renderLeadershipStaffCheckbox();
        renderCheckboxes(elements['area-filters'], areas, state.areas);
        entryDialog.setEmployees(state.data.employees.filter(employee => employee.canManage));
        shiftDefaults.set(state.data.shiftDefaults || {});
        document.getElementById('adc-open-meeting-finder').disabled = false;
        renderSelected();
    }

    function renderLeadershipStaffCheckbox() {
        const label = node('label');
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = state.showLeadershipStaff;
        input.addEventListener('change', () => {
            state.showLeadershipStaff = input.checked;
            if (!input.checked) for (const role of leadershipStaffRoles) state.roles.delete(role);
            state.persist(); renderTable();
        });
        label.append(input, document.createTextNode(` ${organization.staffBlockLabel} anzeigen`));
        elements['role-filters'].append(label);
    }

    function renderCheckboxes(container, values, selected) {
        container.replaceChildren(...values.map(value => { const label = node('label'); const input = document.createElement('input'); input.type = 'checkbox'; input.checked = selected.has(value); input.addEventListener('change', () => { state.emptyOwnProfile = false; input.checked ? selected.add(value) : selected.delete(value); state.persist(); renderTable(); }); const text = state.data.employees.some(employee => employee.roles.includes(value)) ? organization.roleLabel(value) : organization.areaLabel(value); label.append(input, document.createTextNode(' ' + text)); return label; }));
    }

    function renderSelected() {
        const people = state.data.employees.filter(employee => state.selected.has(employee.uid));
        if (!people.length) { elements['selected-people'].replaceChildren(node('li', 'Keine explizite Auswahl – Gruppenfilter gelten.')); return; }
        elements['selected-people'].replaceChildren(...people.map(employee => { const item = node('li'); const button = node('button', `${employee.displayName} entfernen`); button.type = 'button'; button.addEventListener('click', () => { state.emptyOwnProfile = false; state.selected.delete(employee.uid); state.persist(); renderSelected(); renderTable(); }); item.append(button); return item; }));
    }

    async function saveEntry(data) {
        try {
            await repository.save({ employeeUid: data.employeeUid, type: data.type, start: data.start, end: data.end, title: data.title }, data.id);
            entryDialog.close();
            show('Eintrag gespeichert.');
            await load();
        } catch (error) {
            show(error, true);
        }
    }

    async function saveShiftDefaults(defaults) {
        try {
            const response = await repository.saveShiftDefaults(defaults);
            state.data.shiftDefaults = response.shiftDefaults;
            shiftDefaults.set(response.shiftDefaults);
            await load();
            show('Persönliche Standard-Dienstzeiten gespeichert.');
        } catch (error) { show(error, true); }
    }

    async function saveOrganization(data) {
        try {
            const response = await repository.saveOrganizationSettings(data);
            applyOrganization(response.organization);
            organizationSettings.set(organization);
            await load();
            await loadSettings();
            show('Organisationseinstellungen gespeichert.');
        } catch (error) { show(error, true); }
    }

    function applyOrganization(data) {
        organization = OrganizationModel.get(data);
        leadershipStaffRoles.clear();
        for (const group of organization.staffRoleGroups()) leadershipStaffRoles.add(group);
    }

    function renderTable() {
        const employees = state.availableEmployees();
        elements['filter-status'].textContent = state.selected.size ? `${employees.length} ausgewählt` : state.roles.size || state.areas.size ? `${employees.length} gefiltert` : 'Alle Personen';
        weekTable.render(employees, state);
        elements['toggle-view'].textContent = state.vertical ? 'Tage als Zeilen' : 'Personen als Zeilen';
        elements['toggle-view'].setAttribute('aria-pressed', String(!state.vertical));
    }

    function isoWeekValue(date) { const value = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate())); const day = value.getUTCDay() || 7; value.setUTCDate(value.getUTCDate() + 4 - day); const yearStart = new Date(Date.UTC(value.getUTCFullYear(), 0, 1)); const week = Math.ceil((((value - yearStart) / 86400000) + 1) / 7); return `${value.getUTCFullYear()}-W${String(week).padStart(2, '0')}`; }

    async function removeEntry(entry) {
        let mode = '';
        if (entry.type === 'shift') {
            try { await repository.remove(entry.id); show('Dienst gelöscht.'); await load(); return; }
            catch (error) {
                if (error.status !== 409 || !error.data?.confirmationRequired) { show(error, true); return; }
                mode = await deletionChoice(error.data.children.length);
            }
            if (mode === null) return;
        } else if (!window.confirm('Termin wirklich löschen?')) return;
        await repository.remove(entry.id, mode); show(mode === 'detach' ? 'Dienst gelöscht; Termine sind jetzt Sperrtermine.' : 'Eintrag gelöscht.'); await load();
    }

    function deletionChoice(count) {
        return new Promise(resolve => { const dialog = document.createElement('dialog'); dialog.className = 'adc-dialog adc-delete-dialog'; let settled = false; const finish = value => { if (settled) return; settled = true; dialog.close(); dialog.remove(); resolve(value); }; dialog.setAttribute('aria-labelledby', 'adc-delete-title'); dialog.append(node('h2', 'Dienst mit Terminen löschen', undefined)); dialog.firstChild.id = 'adc-delete-title'; dialog.append(node('p', `Der Dienst enthält ${count} Termin(e). Was soll damit geschehen?`)); [['delete','Dienst und Termine löschen'],['detach','Nur Dienst löschen; Termine als Sperrtermine behalten'],[null,'Abbrechen']].forEach(([value,label]) => { const button = node('button', label); button.type = 'button'; button.addEventListener('click', () => finish(value)); dialog.append(button); }); dialog.addEventListener('cancel', event => { event.preventDefault(); finish(null); }); document.body.append(dialog); dialog.showModal(); });
    }

    async function load() {
        const sunday = new Date(state.monday); sunday.setDate(sunday.getDate() + 6); elements['week-label'].textContent = `${state.monday.toLocaleDateString('de-DE')} – ${sunday.toLocaleDateString('de-DE')}`; elements['week-number'].value = isoWeekValue(state.monday);
        try { state.data = await repository.week(isoDay(state.monday)); state.data.entries = EntryModel.get_all(state.data.entries); applyOrganization(state.data.organization); state.applyInitialFilters(); renderFilters(); renderTable(); tabs.show(state.activeTab, false); show(''); } catch (error) { show(error, true); }
    }

    async function loadSettings() {
        let data; try { data = await repository.settings(); } catch (_) { return; }
        elements.settings.hidden = false;
        applyOrganization(data.organization);
        organizationSettings.set(organization);
        const labels = new Map((data.peerOptions || []).map(option => [option.groupId, option.label]));
        elements['peer-settings'].replaceChildren(...Object.entries(data.peerEditing).map(([group, enabled]) => { const label = node('label'); const input = document.createElement('input'); input.type = 'checkbox'; input.name = group; input.checked = enabled; label.append(input, document.createTextNode(` ${labels.get(group) || organization.roleLabel(group)}`)); return label; }));
    }

    document.getElementById('adc-previous-week').addEventListener('click', () => { state.monday.setDate(state.monday.getDate() - 7); load(); });
    document.getElementById('adc-next-week').addEventListener('click', () => { state.monday.setDate(state.monday.getDate() + 7); load(); });
    document.getElementById('adc-open-meeting-finder').addEventListener('click', () => meetingFinder.open(isoDay(state.monday), state.data.employees, [...state.selected]));
    document.getElementById('adc-save-default').addEventListener('click', async () => {
        try {
            await repository.savePreferences(state.toPreference());
            show('Aktuelle Filter und Ansicht wurden zum persönlichen Standard gemacht.');
        } catch (error) { show(error, true); }
    });
    elements['toggle-view'].addEventListener('click', () => { state.vertical = !state.vertical; state.persist(); renderTable(); });
    elements['week-number'].addEventListener('change', event => { if (event.target.value) { const [year, week] = event.target.value.split('-W').map(Number); const januaryFourth = new Date(year, 0, 4); state.monday = startOfWeek(januaryFourth); state.monday.setDate(state.monday.getDate() + (week - 1) * 7); load(); } });
    elements['person-search'].addEventListener('input', event => { const query = event.target.value.trim().toLocaleLowerCase('de-DE'); const matches = query ? state.data.employees.filter(employee => employee.displayName.toLocaleLowerCase('de-DE').includes(query) && !state.selected.has(employee.uid)).slice(0, 12) : []; elements['search-results'].replaceChildren(...matches.map(employee => { const item = node('li'); const button = node('button', `${employee.displayName} auswählen`); button.type = 'button'; button.addEventListener('click', () => { state.emptyOwnProfile = false; state.selected.add(employee.uid); state.persist(); elements['person-search'].value = ''; elements['search-results'].replaceChildren(); renderSelected(); renderTable(); }); item.append(button); return item; })); });
    elements['calendar-body'].addEventListener('click', event => {
        const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
        if (!button) return;
        const cell = button.closest('td[data-employee-uid][data-day]');
        if (!cell) return;
        const employee = state.data.employees.find(item => item.uid === cell.dataset.employeeUid);
        if (!employee?.canManage) return;
        if (button.dataset.action === 'add-entry') {
            entryDialog.open({ employee, day: new Date(`${cell.dataset.day}T12:00:00`), type: button.dataset.entryType });
            return;
        }
        const entry = state.data.entries.find(item => item.id === Number(button.dataset.entryId));
        if (!entry) return;
        if (button.dataset.action === 'edit-entry') entryDialog.open({ employee, day: new Date(entry.start), type: entry.type, entry });
        if (button.dataset.action === 'delete-entry') removeEntry(entry);
    });
    elements['settings-form'].addEventListener('submit', async event => { event.preventDefault(); const peerEditing = Object.fromEntries([...elements['peer-settings'].querySelectorAll('input')].map(input => [input.name, input.checked])); try { await repository.saveSettings(peerEditing); show('Bearbeitungsrechte gespeichert.'); await load(); } catch (error) { show(error, true); } });
    load();
    loadSettings();
}());
