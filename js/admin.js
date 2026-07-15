(function() {
    'use strict';

    const confirmation = document.getElementById('adc-demo-confirm');
    const button = document.getElementById('adc-demo-install');
    const notice = document.getElementById('adc-demo-notice');
    if (!confirmation || !button || !notice) return;

    const client = new window.LocalBase.api.ApiClient({ appId: 'adcalendar' });
    confirmation.addEventListener('change', () => { button.disabled = !confirmation.checked; });
    button.addEventListener('click', async () => {
        if (!confirmation.checked) return;
        button.disabled = true;
        notice.hidden = false;
        notice.className = 'adc-admin-notice';
        notice.textContent = 'Demo-Pack wird geprüft und installiert …';
        try {
            const response = await client.request('/api/admin/demo-pack/install', { method: 'POST', body: '{}' });
            const result = response.result;
            notice.classList.add('is-success');
            notice.textContent = `${result.accounts.createdUsers} Konten und ${result.accounts.createdGroups} Gruppen angelegt; Kalenderdaten für ${result.createdCalendars} Personen erzeugt.`;
            confirmation.checked = false;
        } catch (error) {
            notice.classList.add('is-error');
            notice.textContent = error.message || 'Das Demo-Pack konnte nicht installiert werden.';
            button.disabled = false;
        }
    });
}());
