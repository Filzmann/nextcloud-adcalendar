import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../js/main.js', import.meta.url), 'utf8');
const repository = readFileSync(new URL('../../js/repositories/calendar-repository.js', import.meta.url), 'utf8');
const model = readFileSync(new URL('../../js/models/calendar-entry.js', import.meta.url), 'utf8');
const organizationModel = readFileSync(new URL('../../js/models/organization.js', import.meta.url), 'utf8');
const calendarCell = readFileSync(new URL('../../js/components/calendar-cell.js', import.meta.url), 'utf8');
const calendarFilters = readFileSync(new URL('../../js/components/calendar-filters.js', import.meta.url), 'utf8');
const entryDialog = readFileSync(new URL('../../js/components/entry-dialog.js', import.meta.url), 'utf8');
const meetingFinder = readFileSync(new URL('../../js/components/meeting-finder.js', import.meta.url), 'utf8');
const shiftDefaults = readFileSync(new URL('../../js/components/shift-defaults.js', import.meta.url), 'utf8');
const stateSource = readFileSync(new URL('../../js/modules/calendar-state.js', import.meta.url), 'utf8');
const entryWorkflow = readFileSync(new URL('../../js/modules/entry-workflow.js', import.meta.url), 'utf8');
const meetingCapabilities = readFileSync(new URL('../../js/modules/meeting-capabilities.js', import.meta.url), 'utf8');
const weekTable = readFileSync(new URL('../../js/components/week-table.js', import.meta.url), 'utf8');
const weekNavigation = readFileSync(new URL('../../js/components/week-navigation.js', import.meta.url), 'utf8');
const tabNavigation = readFileSync(new URL('../../js/components/tab-navigation.js', import.meta.url), 'utf8');
for (const contract of [
    'weekTable.render(employees, state)',
    'entryWorkflow.save(data)',
    'repository.savePreferences(state.toPreference())',
    'applyOrganization(data.organization)',
    'tabs.show(state.activeTab, false)',
    'shiftDefaults.set(state.data.shiftDefaults || {})',
    'repository.saveShiftDefaults(defaults)',
    'meetingFinder.open(isoDay(state.monday)',
    'meetingCapabilities.apply(data.entries, data.employees)',
    'const sequence = ++loadSequence',
    'if (sequence !== loadSequence) return;',
    'if (sequence === loadSequence) show(error, true)',
]) {
    if (!source.includes(contract)) throw new Error(`Frontend-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class EntryWorkflow', "['delete', 'Dienst und Termine löschen']", "['detach', 'Nur Dienst löschen; Termine als Sperrtermine behalten']", "dialog.addEventListener('cancel'", 'this.dialog.open({ employee', 'this.repository.updateMeeting(existing.meetingUid', 'this.repository.removeMeeting(entry.meetingUid)', 'if (!employee?.canManage) return;', 'this.show(error, true)']) {
    if (!entryWorkflow.includes(contract)) throw new Error(`Eintragsworkflow-Vertrag fehlt: ${contract}`);
}
const workflowContext = { window: { confirm: () => true }, document: {}, Element: class {}, Date, Number, Promise };
runInNewContext(entryWorkflow, workflowContext);
const workflow = Object.create(workflowContext.window.AdCalendar.modules.EntryWorkflow.prototype);
let deletionError = null;
workflow.repository = { remove: async () => { throw new Error('Löschen fehlgeschlagen'); } };
workflow.show = (error, isError) => { if (isError) deletionError = error; };
workflow.reload = async () => {};
await workflow.remove({ id: 7, type: 'appointment', meetingUid: null });
if (deletionError?.message !== 'Löschen fehlgeschlagen') throw new Error('Fehler beim abschließenden Löschen wird nicht angezeigt.');
const capabilitiesContext = { window: {}, Map };
runInNewContext(meetingCapabilities, capabilitiesContext);
const capabilities = new capabilitiesContext.window.AdCalendar.modules.MeetingCapabilities();
const mixedMeeting = [{ employeeUid: 'a', meetingUid: 'meeting-1' }, { employeeUid: 'b', meetingUid: 'meeting-1' }];
capabilities.apply(mixedMeeting, [{ uid: 'a', canManage: true }, { uid: 'b', canManage: false }]);
if (mixedMeeting.some(entry => entry.canManageMeeting !== false)) throw new Error('Meeting mit nicht bearbeitbarer Person wurde in der UI freigegeben.');
const manageableMeeting = [{ employeeUid: 'a', meetingUid: 'meeting-2' }, { employeeUid: 'b', meetingUid: 'meeting-2' }];
capabilities.apply(manageableMeeting, [{ uid: 'a', canManage: true }, { uid: 'b', canManage: true }]);
if (manageableMeeting.some(entry => entry.canManageMeeting !== true)) throw new Error('Vollständig bearbeitbares Meeting wurde in der UI gesperrt.');
for (const contract of ['class CalendarFilters', 'this.renderLeadershipStaffCheckbox()', 'this.state.selected.clear()', 'this.state.persist()', 'this.onChange()', 'Keine explizite Auswahl – Gruppenfilter gelten.', 'this.organization().staffBlockLabel']) {
    if (!calendarFilters.includes(contract)) throw new Error(`Kalenderfilter-Komponentenvertrag fehlt: ${contract}`);
}
for (const contract of ['class TabNavigation', "addEventListener('click'", "this.show('settings')", "this.onChange(active)"]) {
    if (!tabNavigation.includes(contract)) throw new Error(`Tab-Komponentenvertrag fehlt: ${contract}`);
}
for (const contract of ['class WeekTable', 'adc-group-heading', 'this.calendarCell.render(entries, employee, absences)', 'organization.staffBlockLabel', 'organization.roleLabel(value)', 'organization.areaLabel(value)', 'groupCell.colSpan = 8', 'this.staffRank(a) - this.staffRank(b)', 'roleNames.slice(1)', "join(' / ')"]) {
    if (!weekTable.includes(contract)) throw new Error(`Wochenmatrix-Komponentenvertrag fehlt: ${contract}`);
}
for (const contract of ['class WeekNavigation', 'this.move(-7)', 'this.move(7)', 'this.state.persist()', 'this.onWeekChange', 'this.onViewChange', 'isoWeekValue(date)', 'Tage als Zeilen', 'Personen als Zeilen']) {
    if (!weekNavigation.includes(contract)) throw new Error(`Wochennavigations-Komponentenvertrag fehlt: ${contract}`);
}
const navigationContext = { window: {}, document: {}, Date, Number, String };
runInNewContext(weekNavigation, navigationContext);
const navigation = Object.create(navigationContext.window.AdCalendar.components.WeekNavigation.prototype);
let navigationPersisted = false; let navigationLoaded = false;
navigation.state = { monday: new Date(2026, 0, 5), persist: () => { navigationPersisted = true; } };
navigation.render = () => {};
navigation.onWeekChange = () => { navigationLoaded = true; };
navigation.select('2026-W29');
if (navigation.isoWeekValue(navigation.state.monday) !== '2026-W29' || !navigationPersisted || !navigationLoaded) {
    throw new Error('Ausgewählte Kalenderwoche wird nicht korrekt berechnet, persistiert und geladen.');
}
const tableContext = { window: {}, document: {}, Date, Number };
runInNewContext(weekTable, tableContext);
const clusterTable = Object.create(tableContext.window.AdCalendar.components.WeekTable.prototype);
clusterTable.organization = () => ({ staffRoleGroups: () => [], staffBlockLabel: 'Leitungen', roleLabel: value => ({'ad-EB':'Einsatzbegleitung','ad-StvBL':'Stellvertretende Büroleitung'}[value] || value), areaLabel: () => 'Nordost', roleOrder: () => 1 });
if (clusterTable.clusterLabel({ roles: ['ad-EB', 'ad-StvBL'], areas: ['ad-Bereich-Nordost'] }) !== 'Einsatzbegleitung (Stellvertretende Büroleitung) · Nordost') {
    throw new Error('Mehrfachrollen werden im Gruppentitel nicht in Klammern dargestellt.');
}
for (const contract of ["params.set('people'", "params.set('roles'", "params.set('areas'", 'this.data.defaultFilters ||', 'this.data.currentUserProfile?.roles', 'if (this.selected.size) return this.selected.has(employee.uid)', 'showLeadershipStaff: this.showLeadershipStaff']) {
    if (!stateSource.includes(contract)) throw new Error(`Kalenderzustandsvertrag fehlt: ${contract}`);
}
if (weekTable.includes("header.append(this.node('th', 'Gesamt'))")) throw new Error('Entfernte Gesamtspalte wird noch gerendert.');
if (source.includes('state.data.summaries')) throw new Error('Entfernter Gesamt-Payload wird noch verwendet.');
for (const contract of ['extends BaseRepository', 'savePreferences(filters)', 'saveShiftDefaults(shiftDefaults)', 'meetingGaps(start, employeeUids, durationMinutes)', 'blockMeeting(start, end, employeeUids, title)', 'updateMeeting(meetingUid, start, end, title)', 'removeMeeting(meetingUid)', "method: id == null ? 'POST' : 'PUT'"]) {
    if (!repository.includes(contract)) throw new Error(`Repository-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class Organization extends BaseModel', 'roleLabel(groupId)', 'areaLabel(groupId)', 'staffRoleGroups()', 'roleOrder(groupId)', 'toArray()']) {
    if (!organizationModel.includes(contract)) throw new Error(`Organisationsmodell-Vertrag fehlt: ${contract}`);
}
for (const contract of ['window.LocalBase.models.Model', 'extends BaseModel', 'toArray()', 'this.defaultDate', 'this.defaultModified', 'this.defaultDeleted', 'this.meetingUid', 'this.canManageMeeting']) {
    if (!model.includes(contract)) throw new Error(`Modell-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class CalendarCell', 'adc-cell-actions', 'adc-entry__children', 'entry.parentEntryId === shift.id', 'entry.canManageMeeting !== false', 'adc-entry__blocked-marker', 'aria-hidden="true">🔒', "data-action=\"add-entry\"", 'data-tooltip="Dienst anlegen"', 'icon-calendar-dark']) {
    if (!calendarCell.includes(contract)) throw new Error(`Kalenderzellen-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class MeetingFinder', 'this.selected = new Set(selected)', 'employeeUids.length < 2', 'this.repository.meetingGaps', 'renderResults(gaps, canBlockAll)', 'In der nächsten Woche suchen', 'abwählen', 'this.repository.blockMeeting', 'Number(this.duration.value)']) {
    if (!meetingFinder.includes(contract)) throw new Error(`Meeting-Finder-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class ShiftDefaults', 'Array.from({ length: 7 }', 'data-field="enabled"', 'this.onSave(this.collect())']) {
    if (!shiftDefaults.includes(contract)) throw new Error(`Dienstzeiten-Komponentenvertrag fehlt: ${contract}`);
}
for (const contract of ['class EntryDialog', 'this.dialog.showModal()', 'this.updateType()', 'this.nextFreeShift', 'setCustomValidity(message)', 'Boolean(entry?.meetingUid)', "entry.type === 'shift'", 'start < new Date(entry.end)', 'end > new Date(entry.start)']) {
    if (!entryDialog.includes(contract)) throw new Error(`Eintragsdialog-Vertrag fehlt: ${contract}`);
}

const componentContext = {
    window: { LocalBase: { ui: { esc: value => String(value ?? '') } } },
    Date,
};
runInNewContext(calendarCell, componentContext);
const cell = new componentContext.window.AdCalendar.components.CalendarCell();
const cellHtml = cell.render([
    { id: 1, type: 'shift', start: '2026-07-06T08:00:00Z', end: '2026-07-06T16:00:00Z', title: '', parentEntryId: null },
    { id: 2, type: 'appointment', start: '2026-07-06T10:00:00Z', end: '2026-07-06T11:00:00Z', title: 'Teamtermin', parentEntryId: 1 },
], { canManage: true });
if (!cellHtml.includes('adc-entry__children') || cellHtml.indexOf('Teamtermin') < cellHtml.indexOf('adc-entry--shift')) {
    throw new Error('Termin wurde nicht sichtbar innerhalb des Dienstes gerendert.');
}
if ((cellHtml.match(/Teamtermin/g) || []).length !== 1) throw new Error('Enthaltener Termin wurde mehrfach gerendert.');
const blockedHtml = cell.render([
    { id: 3, type: 'appointment', start: '2026-07-06T18:00:00Z', end: '2026-07-06T19:00:00Z', title: 'Blockiert', parentEntryId: null },
], { canManage: true });
if (!blockedHtml.includes('adc-entry--blocked') || !blockedHtml.includes('adc-entry__blocked-marker') || !blockedHtml.includes('Sperrtermin')) throw new Error('Sperrtermin wird nicht kräftig und textlich als Sperre gekennzeichnet.');

const tabContext = { window: {} };
runInNewContext(tabNavigation, tabContext);
const fakeButton = () => ({ listeners: {}, attributes: {}, addEventListener(type, listener) { this.listeners[type] = listener; }, setAttribute(name, value) { this.attributes[name] = value; }, click() { this.listeners.click(); } });
const calendarButton = fakeButton(); const settingsButton = fakeButton();
const calendarPanel = { hidden: false }; const settingsPanel = { hidden: true }; const tabChanges = [];
new tabContext.window.AdCalendar.components.TabNavigation({ calendarButton, settingsButton, calendarPanel, settingsPanel, onChange: tab => tabChanges.push(tab) });
settingsButton.click();
if (!calendarPanel.hidden || settingsPanel.hidden || settingsButton.attributes['aria-selected'] !== 'true' || tabChanges[0] !== 'settings') {
    throw new Error('Klick auf den Einstellungs-Tab schaltet die Panels nicht um.');
}

const stateContext = { window: {}, Date, Set, URLSearchParams, Number };
runInNewContext(stateSource, stateContext);
const historyCalls = [];
const CalendarState = stateContext.window.AdCalendar.modules.CalendarState;
const filterState = new CalendarState(new Set(['ad-PDL']), { search: '', pathname: '/apps/adcalendar/' }, { replaceState: (...args) => historyCalls.push(args) }).restore();
filterState.data = {
    defaultFilters: null,
    currentUserProfile: { roles: ['ad-Buero'], areas: ['ad-Bereich-Sued'] },
    employees: [
        { uid: 'bo', roles: ['ad-Buero'], areas: ['ad-Bereich-Sued'] },
        { uid: 'pdl', roles: ['ad-PDL'], areas: [] },
    ],
};
filterState.applyInitialFilters();
if (filterState.showLeadershipStaff || filterState.availableEmployees().map(employee => employee.uid).join(',') !== 'bo') throw new Error('Eigengruppen-Standard wurde beim Refactoring verändert.');
filterState.showLeadershipStaff = true; filterState.persist();
if (filterState.availableEmployees().map(employee => employee.uid).join(',') !== 'bo,pdl' || !historyCalls.at(-1)[2].includes('staff=visible')) throw new Error('Der gemeinsame Stabs-/GF-Block wird nicht unabhängig zugeschaltet oder in der URL persistiert.');
filterState.showLeadershipStaff = false; filterState.persist();
if (filterState.availableEmployees().some(employee => employee.uid === 'pdl') || !historyCalls.at(-1)[2].includes('staff=hidden')) throw new Error('Stabsfilter oder URL-Persistenz wurde beim Refactoring verändert.');

const staffDefaultState = new CalendarState(new Set(['ad-PDL']), { search: '', pathname: '/apps/adcalendar/' }, { replaceState() {} }).restore();
staffDefaultState.data = {
    defaultFilters: null,
    currentUserProfile: { roles: ['ad-PDL'], areas: [] },
    employees: filterState.data.employees,
};
staffDefaultState.applyInitialFilters();
if (!staffDefaultState.showLeadershipStaff || staffDefaultState.availableEmployees().map(employee => employee.uid).join(',') !== 'pdl') throw new Error('Mitglieder des gemeinsamen Leitungs-/Stabsblocks erhalten nicht ihren passenden Erststandard.');

const dialogContext = { window: {}, document: {}, Date };
runInNewContext(entryDialog, dialogContext);
const dialog = Object.create(dialogContext.window.AdCalendar.components.EntryDialog.prototype);
let startValidity = '';
let endValidity = '';
dialog.entries = () => [{ id: 7, employeeUid: 'demo', type: 'shift', start: '2026-07-06T10:00:00Z', end: '2026-07-06T18:00:00Z' }];
dialog.shiftDefaults = () => ({ '1': { enabled: true, start: '07:00', end: '15:00' } });
dialog.fields = {
    type: { value: 'shift' }, employee: { value: 'demo' },
    start: { value: '2026-07-06T13:00', setCustomValidity: value => { startValidity = value; } },
    end: { value: '2026-07-06T14:00', setCustomValidity: value => { endValidity = value; } },
    'entry-id': { value: '' }, 'time-help': { textContent: '' },
};
if (dialog.validate() !== false || startValidity === '' || endValidity === '') throw new Error('Dienstüberschneidung wurde im Dialog nicht blockiert.');
const suggestion = dialog.nextFreeShift('demo', new Date('2026-07-06T12:00:00'));
if (suggestion.start < new Date('2026-07-06T18:00:00Z') && suggestion.end > new Date('2026-07-06T10:00:00Z')) {
    throw new Error('Vorgeschlagener Dienst überschneidet den vorhandenen Dienst.');
}
dialog.entries = () => [];
dialog.shiftDefaults = () => ({ '1': { enabled: true, start: '20:00', end: '06:00' } });
const overnightSuggestion = dialog.nextFreeShift('demo', new Date('2026-07-06T12:00:00'));
if (overnightSuggestion.end - overnightSuggestion.start !== 10 * 60 * 60 * 1000) throw new Error('Persönlicher Nachtdienststandard wurde nicht übernommen.');
console.log('Calendar workflow smoke: OK');
