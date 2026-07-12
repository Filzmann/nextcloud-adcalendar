import { readFileSync } from 'node:fs';

const source = readFileSync(new URL('../../js/main.js', import.meta.url), 'utf8');
const repository = readFileSync(new URL('../../js/repositories/calendar-repository.js', import.meta.url), 'utf8');
const model = readFileSync(new URL('../../js/models/calendar-entry.js', import.meta.url), 'utf8');
for (const contract of [
    "['delete','Dienst und Termine löschen']",
    "['detach','Nur Dienst löschen; Termine als Sperrtermine behalten']",
    "params.set('people'",
    "params.set('roles'",
    "params.set('areas'",
    "state.vertical = !state.vertical",
    "document.getElementById('adc-type').disabled = true",
    "if (state.selected.size) return state.selected.has(employee.uid)",
    "adc-group-heading",
    "isoWeekValue",
    "new Date(entry.start) < dayEnd && new Date(entry.end) > day",
    "return date.toISOString()",
    "dialog.addEventListener('cancel'",
]) {
    if (!source.includes(contract)) throw new Error(`Frontend-Vertrag fehlt: ${contract}`);
}
for (const contract of ['extends BaseRepository', 'saveSettings(peerEditing)', "method: id == null ? 'POST' : 'PUT'"]) {
    if (!repository.includes(contract)) throw new Error(`Repository-Vertrag fehlt: ${contract}`);
}
for (const contract of ['window.LocalBase.models.Model', 'extends BaseModel', 'toArray()']) {
    if (!model.includes(contract)) throw new Error(`Modell-Vertrag fehlt: ${contract}`);
}
console.log('Calendar workflow smoke: OK');
