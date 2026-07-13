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
    const elements = Object.fromEntries(['calendar-body','calendar-head','filter-status'].map(id => [id, document.getElementById(`adc-${id}`)]));
    const state = new window.AdCalendar.modules.CalendarState(leadershipStaffRoles).restore();
    const meetingCapabilities = new window.AdCalendar.modules.MeetingCapabilities();
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
    let entryWorkflow;
    const entryDialog = new window.AdCalendar.components.EntryDialog({
        entries: () => state.data?.entries || [],
        shiftDefaults: () => state.data?.shiftDefaults || {},
        onSubmit: data => entryWorkflow.save(data),
    });
    const meetingFinder = new window.AdCalendar.components.MeetingFinder({
        repository,
        onError: error => show(error, true),
        onBlocked: async () => { await load(); show('Meeting wurde für alle ausgewählten Personen blockiert.'); },
    });
    const shiftDefaults = new window.AdCalendar.components.ShiftDefaults({ onSave: saveShiftDefaults });
    const calendarFilters = new window.AdCalendar.components.CalendarFilters({
        state,
        organization: () => organization,
        leadershipStaffRoles,
        onChange: renderTable,
    });
    const weekNavigation = new window.AdCalendar.components.WeekNavigation({
        state,
        onWeekChange: load,
        onViewChange: renderTable,
    });
    entryWorkflow = new window.AdCalendar.modules.EntryWorkflow({
        repository,
        state,
        dialog: entryDialog,
        body: elements['calendar-body'],
        show,
        reload: load,
    });

    function isoDay(value) { const year = value.getFullYear(); const month = String(value.getMonth() + 1).padStart(2, '0'); const day = String(value.getDate()).padStart(2, '0'); return `${year}-${month}-${day}`; }
    function show(message, error) { if (error) notice.error(message); else if (message) notice.success(message); else notice.clear(); }

    function renderFilters() {
        calendarFilters.render();
        entryDialog.setEmployees(state.data.employees.filter(employee => employee.canManage));
        shiftDefaults.set(state.data.shiftDefaults || {});
        document.getElementById('adc-open-meeting-finder').disabled = false;
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

    function applyOrganization(data) {
        organization = OrganizationModel.get(data);
        leadershipStaffRoles.clear();
        for (const group of organization.staffRoleGroups()) leadershipStaffRoles.add(group);
    }

    function renderTable() {
        const employees = state.availableEmployees();
        elements['filter-status'].textContent = state.selected.size ? `${employees.length} ausgewählt` : state.roles.size || state.areas.size ? `${employees.length} gefiltert` : 'Alle Personen';
        weekTable.render(employees, state);
    }

    async function load() {
        weekNavigation.render();
        try { state.data = await repository.week(isoDay(state.monday)); state.data.entries = EntryModel.get_all(state.data.entries); meetingCapabilities.apply(state.data.entries, state.data.employees); applyOrganization(state.data.organization); state.applyInitialFilters(); renderFilters(); renderTable(); tabs.show(state.activeTab, false); show(''); } catch (error) { show(error, true); }
    }

    document.getElementById('adc-open-meeting-finder').addEventListener('click', () => meetingFinder.open(isoDay(state.monday), state.data.employees, [...state.selected]));
    document.getElementById('adc-save-default').addEventListener('click', async () => {
        try {
            await repository.savePreferences(state.toPreference());
            show('Aktuelle Filter und Ansicht wurden zum persönlichen Standard gemacht.');
        } catch (error) { show(error, true); }
    });
    load();
}());
