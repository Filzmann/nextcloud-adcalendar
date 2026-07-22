import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../js/admin.js', import.meta.url), 'utf8');
const element = (value = '') => ({
    value, textContent: '', hidden: false, disabled: false, dataset: {}, listeners: {},
    className: '', classList: { add() {}, remove() {} },
    addEventListener(type, listener) { this.listeners[type] = listener; },
});
const form = element();
const clientId = element('client-id');
const secret = element('new-secret');
const status = element();
const remove = element();
const copy = element();
const redirect = element('https://cloud.example.test/callback');
const elements = {
    'adc-google-oauth-form': form,
    'adc-google-client-id': clientId,
    'adc-google-client-secret': secret,
    'adc-google-oauth-status': status,
    'adc-google-oauth-remove': remove,
    'adc-google-copy-redirect': copy,
    'adc-google-redirect-uri': redirect,
};
const calls = [];
class ApiClient {
    async request(path, options) {
        calls.push([path, options]);
        return { googleOAuth: options.method === 'DELETE'
            ? { configured: false, clientId: '', secretConfigured: false, redirectUri: redirect.value }
            : { configured: true, clientId: clientId.value, secretConfigured: true, redirectUri: redirect.value } };
    }
}
let copied = '';
const context = {
    window: { LocalBase: { api: { ApiClient } }, confirm: () => true },
    document: { getElementById: id => elements[id] || null },
    navigator: { clipboard: { writeText: async value => { copied = value; } } },
};
runInNewContext(source, context);

await form.listeners.submit({ preventDefault() {} });
if (calls[0][0] !== '/api/admin/google-oauth' || calls[0][1].method !== 'PUT' || JSON.parse(calls[0][1].body).clientSecret !== 'new-secret') throw new Error('Google-OAuth-Adminformular speichert nicht über den geschützten API-Pfad.');
if (secret.value !== '' || remove.disabled || !status.textContent.includes('konfiguriert')) throw new Error('Google-OAuth-Adminformular behält das Secret oder aktualisiert den Status nicht.');
await copy.listeners.click();
if (copied !== redirect.value) throw new Error('Google-Redirect-URI kann nicht kopiert werden.');
await remove.listeners.click();
if (calls[1][1].method !== 'DELETE' || !remove.disabled || clientId.value !== '') throw new Error('Google-OAuth-Konfiguration kann nicht sicher entfernt werden.');

console.log('Admin settings smoke: OK');
