// Attendance toggle functionality
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('attendance-toggle');
    if (!toggleBtn) {
        console.log('[Attendance] Toggle button not found');
        return;
    }

    console.log('[Attendance] JS loaded successfully');

    toggleBtn.addEventListener('click', async function (e) {
        e.preventDefault();
        const eventId = this.dataset.eventId;

        // Get base URL from <base> tag or fallback to origin
        const base = document.querySelector('base');
        const baseUrl = base ? base.href : (window.location.origin + '/');
        const url = baseUrl + 'agenda/event/' + eventId + '/attendance';

        console.log('[Attendance] Toggling for event:', eventId);
        console.log('[Attendance] Fetch URL:', url);

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            console.log('[Attendance] Response status:', response.status);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('[Attendance] Server error:', response.status, errorText);
                alert('Erreur: ' + response.status);
                return;
            }

            const data = await response.json();
            console.log('[Attendance] Response data:', data);

            // Update button state
            this.dataset.attending = data.isAttending ? '1' : '0';
            this.className = data.isAttending ? 'button alert' : 'button success';

            const icon = this.querySelector('i');
            const text = this.querySelector('span');

            if (data.isAttending) {
                icon.className = 'fa-solid fa-times';
                text.textContent = 'Annuler ma présence';
            } else {
                icon.className = 'fa-solid fa-check';
                text.textContent = 'Je serai présent';
            }

            // Update counts
            document.getElementById('attendance-total').textContent = data.count.total;
            const registeredEl = document.getElementById('attendance-registered');
            const anonymousEl = document.getElementById('attendance-anonymous');
            if (registeredEl) registeredEl.textContent = data.count.registered;
            if (anonymousEl) anonymousEl.textContent = data.count.anonymous;

            console.log('[Attendance] Updated successfully - Total:', data.count.total, 'Registered:', data.count.registered, 'Anonymous:', data.count.anonymous);

        } catch (error) {
            console.error('[Attendance] Fetch error:', error);
            alert('Erreur réseau: ' + error.message);
        }
    });
});
