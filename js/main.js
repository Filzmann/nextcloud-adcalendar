(function () {
    'use strict';
    const root = OC.generateUrl('/apps/adcalendar');
    const elements = Object.fromEntries(['week-label','calendar-body','calendar-head','notice','employee','entry-form','entry-id','cancel-edit','role-filters','area-filters','person-search','search-results','selected-people','week-number','toggle-view','settings','settings-form','peer-settings'].map(id => [id, document.getElementById(`adc-${id}`)]));
    const state = { monday: startOfWeek(new Date()), data: null, vertical: true, selected: new Set(), roles: new Set(), areas: new Set() };
    restoreState();

    function startOfWeek(value) { const result = new Date(value); const weekday = result.getDay() || 7; result.setDate(result.getDate() - weekday + 1); result.setHours(0, 0, 0, 0); return result; }
    function isoDay(value) { const year = value.getFullYear(); const month = String(value.getMonth() + 1).padStart(2, '0'); const day = String(value.getDate()).padStart(2, '0'); return `${year}-${month}-${day}`; }
    function node(tag, value, className) { const result = document.createElement(tag); if (value !== undefined) result.textContent = value; if (className) result.className = className; return result; }
    function show(message, error) { elements.notice.textContent = message; elements.notice.className = error ? 'adc-notice adc-notice--error' : 'adc-notice'; }
    function restoreState() { const params = new URLSearchParams(window.location.search); if (params.get('week')) state.monday = startOfWeek(new Date(`${params.get('week')}T12:00:00`)); state.vertical = params.get('view') !== 'days'; for (const value of (params.get('people') || '').split(',').filter(Boolean)) state.selected.add(value); for (const value of (params.get('roles') || '').split(',').filter(Boolean)) state.roles.add(value); for (const value of (params.get('areas') || '').split(',').filter(Boolean)) state.areas.add(value); }
    function persistState() { const params = new URLSearchParams(); params.set('week', isoDay(state.monday)); if (!state.vertical) params.set('view', 'days'); if (state.selected.size) params.set('people', [...state.selected].join(',')); if (state.roles.size) params.set('roles', [...state.roles].join(',')); if (state.areas.size) params.set('areas', [...state.areas].join(',')); window.history.replaceState(null, '', `${window.location.pathname}?${params}`); }
    async function request(path, options = {}) { const response = await fetch(root + path, { credentials: 'same-origin', headers: { 'Content-Type': 'application/json', requesttoken: OC.requestToken }, ...options }); const data = await response.json(); return { response, data }; }
    async function api(path, options) { const { response, data } = await request(path, options); if (!response.ok) throw new Error(data.error || 'Anfrage fehlgeschlagen.'); return data; }

    function availableEmployees() {
        if (!state.data) return [];
        return state.data.employees.filter(employee => {
            if (state.selected.size) return state.selected.has(employee.uid);
            if (state.roles.size && !employee.roles.some(role => state.roles.has(role))) return false;
            if (state.areas.size && !employee.areas.some(area => state.areas.has(area))) return false;
            return true;
        });
    }

    function renderFilters() {
        const roles = [...new Set(state.data.employees.flatMap(employee => employee.roles))].sort();
        const areas = [...new Set(state.data.employees.flatMap(employee => employee.areas))].sort();
        renderCheckboxes(elements['role-filters'], roles, state.roles);
        renderCheckboxes(elements['area-filters'], areas, state.areas);
        elements.employee.replaceChildren(...state.data.employees.filter(employee => employee.canManage).map(employee => { const option = node('option', employee.displayName); option.value = employee.uid; return option; }));
        renderSelected();
    }

    function renderCheckboxes(container, values, selected) {
        container.replaceChildren(...values.map(value => { const label = node('label'); const input = document.createElement('input'); input.type = 'checkbox'; input.checked = selected.has(value); input.addEventListener('change', () => { input.checked ? selected.add(value) : selected.delete(value); persistState(); renderTable(); }); label.append(input, document.createTextNode(' ' + value.replace('ad-', '').replace('Bereich-', ''))); return label; }));
    }

    function renderSelected() {
        const people = state.data.employees.filter(employee => state.selected.has(employee.uid));
        if (!people.length) { elements['selected-people'].replaceChildren(node('li', 'Keine explizite Auswahl – Gruppenfilter gelten.')); return; }
        elements['selected-people'].replaceChildren(...people.map(employee => { const item = node('li'); const button = node('button', `${employee.displayName} entfernen`); button.type = 'button'; button.addEventListener('click', () => { state.selected.delete(employee.uid); persistState(); renderSelected(); renderTable(); }); item.append(button); return item; }));
    }

    function entryNode(entry, employee) {
        const type = entry.type === 'shift' ? 'Dienst' : entry.parentEntryId === null ? 'Sperrtermin' : 'Termin';
        const item = node('div', undefined, `adc-entry adc-entry--${type === 'Sperrtermin' ? 'blocked' : entry.type}`);
        item.append(node('span', `${type}: ${new Date(entry.start).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'})}–${new Date(entry.end).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'})}${entry.title ? ' · ' + entry.title : ''}`));
        if (employee.canManage) {
            const edit = node('button', `${type} bearbeiten`); edit.type = 'button'; edit.addEventListener('click', () => editEntry(entry));
            const remove = node('button', `${type} löschen`); remove.type = 'button'; remove.addEventListener('click', () => removeEntry(entry)); item.append(edit, remove);
        }
        return item;
    }

    function localDateTime(value) { const date = new Date(value); const shifted = new Date(date.getTime() - date.getTimezoneOffset() * 60000); return shifted.toISOString().slice(0, 16); }
    function editEntry(entry) { elements['entry-id'].value = entry.id; elements.employee.value = entry.employeeUid; document.getElementById('adc-type').value = entry.type; document.getElementById('adc-type').disabled = true; document.getElementById('adc-start').value = localDateTime(entry.start); document.getElementById('adc-end').value = localDateTime(entry.end); document.getElementById('adc-title').value = entry.title; elements['cancel-edit'].hidden = false; elements['entry-form'].scrollIntoView({behavior:'smooth',block:'center'}); }
    function resetForm() { elements['entry-form'].reset(); elements['entry-id'].value = ''; document.getElementById('adc-type').disabled = false; elements['cancel-edit'].hidden = true; }

    function cellFor(employee, day) { const cell = document.createElement('td'); state.data.entries.filter(entry => entry.employeeUid === employee.uid && entry.start.slice(0, 10) === isoDay(day)).forEach(entry => cell.append(entryNode(entry, employee))); return cell; }

    function renderTable() {
        const employees = availableEmployees();
        if (state.vertical) renderVertical(employees); else renderHorizontal(employees);
        elements['toggle-view'].textContent = state.vertical ? 'Tage als Zeilen' : 'Personen als Zeilen';
        elements['toggle-view'].setAttribute('aria-pressed', String(!state.vertical));
    }

    function renderVertical(employees) {
        const header = document.createElement('tr'); header.append(node('th', 'Mitarbeiter*in'));
        for (let offset = 0; offset < 7; offset++) { const day = new Date(state.monday); day.setDate(day.getDate() + offset); header.append(node('th', day.toLocaleDateString('de-DE', {weekday:'short',day:'2-digit',month:'2-digit'}))); }
        header.append(node('th', 'Gesamt')); elements['calendar-head'].replaceChildren(header);
        const rows = []; let previousCluster = null;
        for (const employee of employees.slice().sort((a, b) => clusterLabel(a).localeCompare(clusterLabel(b), 'de') || a.displayName.localeCompare(b.displayName, 'de'))) {
            const cluster = clusterLabel(employee);
            if (!state.selected.size && cluster !== previousCluster) { const groupRow = document.createElement('tr'); const groupCell = node('th', cluster, 'adc-group-heading'); groupCell.colSpan = 9; groupRow.append(groupCell); rows.push(groupRow); previousCluster = cluster; }
            const row = document.createElement('tr'); const name = node('th', employee.displayName, state.selected.has(employee.uid) ? 'adc-selected' : ''); name.scope = 'row'; row.append(name); for (let offset = 0; offset < 7; offset++) { const day = new Date(state.monday); day.setDate(day.getDate() + offset); row.append(cellFor(employee, day)); } const summary = state.data.summaries[employee.uid]; row.append(node('td', `${summary.shiftCount} Dienste · ${(summary.shiftMinutes / 60).toLocaleString('de-DE')} Std.`)); rows.push(row);
        }
        elements['calendar-body'].replaceChildren(...rows);
    }

    function clusterLabel(employee) { const roles = employee.roles.map(value => value.replace('ad-', '').replace('Stab-', 'Stab ')).join(', ') || 'Ohne Fachrolle'; const areas = employee.areas.map(value => value.replace('ad-Bereich-', '')).join(', '); return areas ? `${roles} · ${areas}` : roles; }

    function isoWeekValue(date) { const value = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate())); const day = value.getUTCDay() || 7; value.setUTCDate(value.getUTCDate() + 4 - day); const yearStart = new Date(Date.UTC(value.getUTCFullYear(), 0, 1)); const week = Math.ceil((((value - yearStart) / 86400000) + 1) / 7); return `${value.getUTCFullYear()}-W${String(week).padStart(2, '0')}`; }

    function renderHorizontal(employees) {
        const header = document.createElement('tr'); header.append(node('th', 'Tag')); employees.forEach(employee => header.append(node('th', employee.displayName, state.selected.has(employee.uid) ? 'adc-selected' : ''))); elements['calendar-head'].replaceChildren(header);
        const rows = []; for (let offset = 0; offset < 7; offset++) { const day = new Date(state.monday); day.setDate(day.getDate() + offset); const row = document.createElement('tr'); const label = node('th', day.toLocaleDateString('de-DE', {weekday:'long',day:'2-digit',month:'2-digit'})); label.scope = 'row'; row.append(label); employees.forEach(employee => row.append(cellFor(employee, day))); rows.push(row); } elements['calendar-body'].replaceChildren(...rows);
    }

    async function removeEntry(entry) {
        let mode = '';
        if (entry.type === 'shift') {
            const first = await request(`/api/entries/${entry.id}`, { method: 'DELETE', body: JSON.stringify({ childMode: '' }) });
            if (first.response.ok) { show('Dienst gelöscht.'); await load(); return; }
            if (!first.data.confirmationRequired) { show(first.data.error || 'Löschen fehlgeschlagen.', true); return; }
            mode = await deletionChoice(first.data.children.length);
            if (mode === null) return;
        } else if (!window.confirm('Termin wirklich löschen?')) return;
        await api(`/api/entries/${entry.id}`, { method: 'DELETE', body: JSON.stringify({ childMode: mode }) }); show(mode === 'detach' ? 'Dienst gelöscht; Termine sind jetzt Sperrtermine.' : 'Eintrag gelöscht.'); await load();
    }

    function deletionChoice(count) {
        return new Promise(resolve => { const dialog = document.createElement('dialog'); dialog.setAttribute('aria-labelledby', 'adc-delete-title'); dialog.append(node('h2', 'Dienst mit Terminen löschen', undefined)); dialog.firstChild.id = 'adc-delete-title'; dialog.append(node('p', `Der Dienst enthält ${count} Termin(e). Was soll damit geschehen?`)); [['delete','Dienst und Termine löschen'],['detach','Nur Dienst löschen; Termine als Sperrtermine behalten'],[null,'Abbrechen']].forEach(([value,label]) => { const button = node('button', label); button.type = 'button'; button.addEventListener('click', () => { dialog.close(); dialog.remove(); resolve(value); }); dialog.append(button); }); document.body.append(dialog); dialog.showModal(); });
    }

    async function load() {
        const sunday = new Date(state.monday); sunday.setDate(sunday.getDate() + 6); elements['week-label'].textContent = `${state.monday.toLocaleDateString('de-DE')} – ${sunday.toLocaleDateString('de-DE')}`; elements['week-number'].value = isoWeekValue(state.monday);
        try { state.data = await api(`/api/week?start=${encodeURIComponent(isoDay(state.monday))}`); persistState(); renderFilters(); renderTable(); show(''); } catch (error) { show(error.message, true); }
    }

    async function loadSettings() {
        const { response, data } = await request('/api/settings');
        if (!response.ok) return;
        elements.settings.hidden = false;
        elements['peer-settings'].replaceChildren(...Object.entries(data.peerEditing).map(([group, enabled]) => { const label = node('label'); const input = document.createElement('input'); input.type = 'checkbox'; input.name = group; input.checked = enabled; label.append(input, document.createTextNode(` ${group}`)); return label; }));
    }

    document.getElementById('adc-previous-week').addEventListener('click', () => { state.monday.setDate(state.monday.getDate() - 7); load(); });
    document.getElementById('adc-next-week').addEventListener('click', () => { state.monday.setDate(state.monday.getDate() + 7); load(); });
    elements['toggle-view'].addEventListener('click', () => { state.vertical = !state.vertical; persistState(); renderTable(); });
    elements['week-number'].addEventListener('change', event => { if (event.target.value) { const [year, week] = event.target.value.split('-W').map(Number); const januaryFourth = new Date(year, 0, 4); state.monday = startOfWeek(januaryFourth); state.monday.setDate(state.monday.getDate() + (week - 1) * 7); load(); } });
    elements['person-search'].addEventListener('input', event => { const query = event.target.value.trim().toLocaleLowerCase('de-DE'); const matches = query ? state.data.employees.filter(employee => employee.displayName.toLocaleLowerCase('de-DE').includes(query) && !state.selected.has(employee.uid)).slice(0, 12) : []; elements['search-results'].replaceChildren(...matches.map(employee => { const item = node('li'); const button = node('button', `${employee.displayName} auswählen`); button.type = 'button'; button.addEventListener('click', () => { state.selected.add(employee.uid); persistState(); elements['person-search'].value = ''; elements['search-results'].replaceChildren(); renderSelected(); renderTable(); }); item.append(button); return item; })); });
    elements['cancel-edit'].addEventListener('click', resetForm);
    elements['entry-form'].addEventListener('submit', async event => { event.preventDefault(); try { const id = elements['entry-id'].value; const payload = { employeeUid: elements.employee.value, type: document.getElementById('adc-type').value, start: document.getElementById('adc-start').value, end: document.getElementById('adc-end').value, title: document.getElementById('adc-title').value }; await api(id ? `/api/entries/${id}` : '/api/entries', { method: id ? 'PUT' : 'POST', body: JSON.stringify(payload) }); show('Eintrag gespeichert.'); resetForm(); await load(); } catch (error) { show(error.message, true); } });
    elements['settings-form'].addEventListener('submit', async event => { event.preventDefault(); const peerEditing = Object.fromEntries([...elements['peer-settings'].querySelectorAll('input')].map(input => [input.name, input.checked])); try { await api('/api/settings', { method: 'PUT', body: JSON.stringify({ peerEditing }) }); show('Bearbeitungsrechte gespeichert.'); await load(); } catch (error) { show(error.message, true); } });
    load();
    loadSettings();
}());
