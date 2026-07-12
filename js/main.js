(function () {
    'use strict';
    const root = OC.generateUrl('/apps/adcalendar');
    const label = document.getElementById('adc-week-label');
    const body = document.getElementById('adc-calendar-body');
    const head = document.getElementById('adc-calendar-head');
    const notice = document.getElementById('adc-notice');
    const employeeSelect = document.getElementById('adc-employee');
    const form = document.getElementById('adc-entry-form');
    let monday = startOfWeek(new Date());

    function startOfWeek(value) { const result = new Date(value); const weekday = result.getDay() || 7; result.setDate(result.getDate() - weekday + 1); result.setHours(0, 0, 0, 0); return result; }
    function isoDay(value) { return value.toISOString().slice(0, 10); }
    function text(tag, value, className) { const node = document.createElement(tag); node.textContent = value; if (className) node.className = className; return node; }
    function show(message, error) { notice.textContent = message; notice.className = error ? 'adc-notice adc-notice--error' : 'adc-notice'; }
    async function api(path, options) { const response = await fetch(root + path, { credentials: 'same-origin', headers: { 'Content-Type': 'application/json', requesttoken: OC.requestToken }, ...options }); const data = await response.json(); if (!response.ok) throw new Error(data.error || 'Anfrage fehlgeschlagen.'); return data; }

    async function render() {
        const sunday = new Date(monday); sunday.setDate(sunday.getDate() + 6);
        label.textContent = `${monday.toLocaleDateString('de-DE')} – ${sunday.toLocaleDateString('de-DE')}`;
        try {
            const state = await api(`/api/week?start=${encodeURIComponent(isoDay(monday))}`);
            employeeSelect.replaceChildren(...state.employees.map(employee => { const option = document.createElement('option'); option.value = employee.uid; option.textContent = employee.displayName; return option; }));
            const headerRow = document.createElement('tr'); headerRow.append(text('th', 'Mitarbeiter*in'));
            for (let offset = 0; offset < 7; offset++) { const day = new Date(monday); day.setDate(day.getDate() + offset); headerRow.append(text('th', day.toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit' }))); }
            headerRow.append(text('th', 'Gesamt')); head.replaceChildren(headerRow);
            const rows = state.employees.map(employee => {
                const row = document.createElement('tr'); const name = text('th', employee.displayName); name.scope = 'row'; row.append(name);
                for (let offset = 0; offset < 7; offset++) { const day = new Date(monday); day.setDate(day.getDate() + offset); const cell = document.createElement('td'); const entries = state.entries.filter(entry => entry.employeeUid === employee.uid && entry.start.slice(0, 10) === isoDay(day)); entries.forEach(entry => { const item = text('div', `${entry.type === 'shift' ? 'Dienst' : entry.isBlocked ? 'Sperrtermin' : 'Termin'}: ${new Date(entry.start).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'})}–${new Date(entry.end).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'})}${entry.title ? ' · ' + entry.title : ''}`, `adc-entry adc-entry--${entry.isBlocked ? 'blocked' : entry.type}`); cell.append(item); }); row.append(cell); }
                const summary = state.summaries[employee.uid]; row.append(text('td', `${summary.shiftCount} Dienste · ${(summary.shiftMinutes / 60).toLocaleString('de-DE')} Std.`)); return row;
            });
            body.replaceChildren(...rows); show('');
        } catch (error) { show(error.message, true); }
    }
    document.getElementById('adc-previous-week').addEventListener('click', () => { monday.setDate(monday.getDate() - 7); render(); });
    document.getElementById('adc-next-week').addEventListener('click', () => { monday.setDate(monday.getDate() + 7); render(); });
    form.addEventListener('submit', async event => { event.preventDefault(); try { await api('/api/entries', { method: 'POST', body: JSON.stringify({ employeeUid: employeeSelect.value, type: document.getElementById('adc-type').value, start: document.getElementById('adc-start').value, end: document.getElementById('adc-end').value, title: document.getElementById('adc-title').value }) }); show('Eintrag gespeichert.'); form.reset(); await render(); } catch (error) { show(error.message, true); } });
    render();
}());
