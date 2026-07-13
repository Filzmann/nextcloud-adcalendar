import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../js/main.js', import.meta.url), 'utf8');
const repository = readFileSync(new URL('../../js/repositories/calendar-repository.js', import.meta.url), 'utf8');
const model = readFileSync(new URL('../../js/models/calendar-entry.js', import.meta.url), 'utf8');
const calendarCell = readFileSync(new URL('../../js/components/calendar-cell.js', import.meta.url), 'utf8');
const entryDialog = readFileSync(new URL('../../js/components/entry-dialog.js', import.meta.url), 'utf8');
const meetingFinder = readFileSync(new URL('../../js/components/meeting-finder.js', import.meta.url), 'utf8');
const shiftDefaults = readFileSync(new URL('../../js/components/shift-defaults.js', import.meta.url), 'utf8');
const stateSource = readFileSync(new URL('../../js/modules/calendar-state.js', import.meta.url), 'utf8');
const weekTable = readFileSync(new URL('../../js/components/week-table.js', import.meta.url), 'utf8');
const tabNavigation = readFileSync(new URL('../../js/components/tab-navigation.js', import.meta.url), 'utf8');
for (const contract of [
    "['delete','Dienst und Termine löschen']",
    "['detach','Nur Dienst löschen; Termine als Sperrtermine behalten']",
    "state.vertical = !state.vertical",
    "isoWeekValue",
    'weekTable.render(employees, state)',
    "entryDialog.open({ employee",
    "dialog.addEventListener('cancel'",
    'repository.savePreferences(state.toPreference())',
    "new Set(['ad-Stab-HR', 'ad-Stab-QMB', 'ad-GF-AS', 'ad-GF-Digi', 'ad-AsdGF-Digi', 'ad-Sekretariat', 'ad-PDL'])",
    'GF, PDL, Stabsstellen und Sekretariat anzeigen',
    'tabs.show(state.activeTab, false)',
    'shiftDefaults.set(state.data.shiftDefaults || {})',
    'repository.saveShiftDefaults(defaults)',
    'meetingFinder.open(isoDay(state.monday)',
]) {
    if (!source.includes(contract)) throw new Error(`Frontend-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class TabNavigation', "addEventListener('click'", "this.show('settings')", "this.onChange(active)"]) {
    if (!tabNavigation.includes(contract)) throw new Error(`Tab-Komponentenvertrag fehlt: ${contract}`);
}
for (const contract of ['class WeekTable', 'adc-group-heading', 'this.calendarCell.render(entries, employee, absences)', "return 'Geschäftsführung, PDL und Stabsstellen'", 'groupCell.colSpan = 8', 'this.staffRank(a) - this.staffRank(b)', 'roleNames.slice(1)', "join(' / ')"]) {
    if (!weekTable.includes(contract)) throw new Error(`Wochenmatrix-Komponentenvertrag fehlt: ${contract}`);
}
const tableContext = { window: {}, document: {}, Date, Number };
runInNewContext(weekTable, tableContext);
const clusterTable = Object.create(tableContext.window.AdCalendar.components.WeekTable.prototype);
clusterTable.leadershipStaffRoles = new Set();
if (clusterTable.clusterLabel({ roles: ['ad-EB', 'ad-StvBL'], areas: ['ad-Bereich-Nordost'] }) !== 'EB (Stv. BL) · Nordost') {
    throw new Error('Mehrfachrollen werden im Gruppentitel nicht in Klammern dargestellt.');
}
for (const contract of ["params.set('people'", "params.set('roles'", "params.set('areas'", 'this.data.defaultFilters ||', 'this.data.currentUserProfile?.roles', 'if (this.selected.size) return this.selected.has(employee.uid)', 'showLeadershipStaff: this.showLeadershipStaff']) {
    if (!stateSource.includes(contract)) throw new Error(`Kalenderzustandsvertrag fehlt: ${contract}`);
}
if (weekTable.includes("header.append(this.node('th', 'Gesamt'))")) throw new Error('Entfernte Gesamtspalte wird noch gerendert.');
if (source.includes('state.data.summaries')) throw new Error('Entfernter Gesamt-Payload wird noch verwendet.');
for (const contract of ['extends BaseRepository', 'saveSettings(peerEditing)', 'savePreferences(filters)', 'saveShiftDefaults(shiftDefaults)', 'meetingGaps(start, employeeUids, durationMinutes)', "method: id == null ? 'POST' : 'PUT'"]) {
    if (!repository.includes(contract)) throw new Error(`Repository-Vertrag fehlt: ${contract}`);
}
for (const contract of ['window.LocalBase.models.Model', 'extends BaseModel', 'toArray()', 'this.defaultDate', 'this.defaultModified', 'this.defaultDeleted']) {
    if (!model.includes(contract)) throw new Error(`Modell-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class CalendarCell', 'adc-cell-actions', 'adc-entry__children', 'entry.parentEntryId === shift.id', "data-action=\"add-entry\"", 'data-tooltip="Dienst anlegen"', 'icon-calendar-dark']) {
    if (!calendarCell.includes(contract)) throw new Error(`Kalenderzellen-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class MeetingFinder', 'this.selected = new Set(selected)', 'employeeUids.length < 2', 'this.repository.meetingGaps', 'renderResults(gaps)']) {
    if (!meetingFinder.includes(contract)) throw new Error(`Meeting-Finder-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class ShiftDefaults', 'Array.from({ length: 7 }', 'data-field="enabled"', 'this.onSave(this.collect())']) {
    if (!shiftDefaults.includes(contract)) throw new Error(`Dienstzeiten-Komponentenvertrag fehlt: ${contract}`);
}
for (const contract of ['class EntryDialog', 'this.dialog.showModal()', 'this.updateType()', 'this.nextFreeShift', 'setCustomValidity(message)', "entry.type === 'shift'", 'start < new Date(entry.end)', 'end > new Date(entry.start)']) {
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
