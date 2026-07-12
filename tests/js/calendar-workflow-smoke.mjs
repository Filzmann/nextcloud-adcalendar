import { readFileSync } from 'node:fs';

const source = readFileSync(new URL('../../js/main.js', import.meta.url), 'utf8');
for (const contract of [
    "['delete','Dienst und Termine löschen']",
    "['detach','Nur Dienst löschen; Termine als Sperrtermine behalten']",
    "method: id ? 'PUT' : 'POST'",
    "params.set('people'",
    "params.set('roles'",
    "params.set('areas'",
    "state.vertical = !state.vertical",
    "document.getElementById('adc-type').disabled = true",
    "body: JSON.stringify({ peerEditing })",
    "if (state.selected.size) return state.selected.has(employee.uid)",
    "adc-group-heading",
    "isoWeekValue",
]) {
    if (!source.includes(contract)) throw new Error(`Frontend-Vertrag fehlt: ${contract}`);
}
console.log('Calendar workflow smoke: OK');
