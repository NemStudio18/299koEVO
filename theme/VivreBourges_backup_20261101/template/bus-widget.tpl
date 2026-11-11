<div class="agglobus-widget" id="agglobus-{{stop_id}}">
    <div class="widget-header">
        <i class="fa-solid fa-bus"></i> Prochains passages : <span class="stop-name">{{stop_name}}</span>
    </div>
    <div class="arrivals-list" id="arrivals-{{stop_id}}">
        <div class="loading">Chargement des horaires...</div>
    </div>
</div>

<style>
.agglobus-widget {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 15px;
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
    margin: 10px 0;
}
.widget-header {
    font-weight: bold;
    margin-bottom: 10px;
    font-size: 1.1em;
}
.arrival-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.arrival-item:last-child { border-bottom: none; }
.time-left {
    font-weight: bold;
    color: #ffd700;
}
</style>

<script>
(function() {
    const stopId = "{{stop_id}}";
    const container = document.getElementById("arrivals-" + stopId);

    async function updateBus() {
        try {
            const response = await fetch("/agglobus/api/rt?stop_id=" + stopId);
            const data = await response.json();
            
            if (data.error || !data.next_buses || data.next_buses.length === 0) {
                container.innerHTML = "Aucun passage prévu";
                return;
            }

            container.innerHTML = data.next_buses.map(b => `
                <div class="arrival-item">
                    <span>${b.trip_id}</span>
                    <span class="time-left">${b.minutes} min</span>
                </div>
            `).join('');
        } catch (e) {
            container.innerHTML = "Erreur de chargement";
        }
    }

    updateBus();
    setInterval(updateBus, 30000);
})();
</script>
