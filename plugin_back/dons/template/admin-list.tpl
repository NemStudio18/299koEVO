<section class="module-card">
    <header>{{ Lang.dons-admin.list-title }}</header>
    
    <div class="tabs-container">
        <ul class="tabs-header">
            <li class="default-tab"><i class="fa-solid fa-list"></i> {{ Lang.dons-admin.list-title }}</li>
            <li><i class="fa-solid fa-gear"></i> {{ Lang.core-settings }}</li>
        </ul>
        
        <ul class="tabs">
            <!-- Onglet Liste des Dons -->
            <li class="tab">
                <div class="stat-grid">
                    <div class="stat-card">
                        <span class="stat-value">{{ totalAmountFormatted }} €</span>
                        <span class="stat-label">{{ Lang.dons-admin.total-raised }}</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ Lang.dons-admin.amount }}</th>
                                <th>{{ Lang.dons-admin.donor }}</th>
                                <th>{{ Lang.dons-admin.email }}</th>
                                <th>{{ Lang.dons-admin.anon }}</th>
                                <th>{{ Lang.dons-admin.gateway }}</th>
                                <th>{{ Lang.dons-admin.status }}</th>
                                <th>{{ Lang.dons-admin.date }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% for don in dons %}
                                <tr>
                                    <td><strong>{{ don.amountFormatted }} €</strong></td>
                                    <td>{{ don.firstName }} {{ don.lastName }}</td>
                                    <td>{{ don.email }}</td>
                                    <td>
                                        {% if don.isAnonymous %}
                                            <span class="badge warning">{{ Lang.dons-admin.yes }}</span>
                                        {% else %}
                                            <span class="badge success">{{ Lang.dons-admin.no }}</span>
                                        {% endif %}
                                    </td>
                                    <td>{{ don.gateway }}</td>
                                    <td>
                                        {% if don.status == "completed" %}
                                            <span class="button small success">{{ Lang.dons-admin.completed }}</span>
                                        {% elseif don.status == "pending" %}
                                            <span class="button small warning">{{ Lang.dons-admin.pending }}</span>
                                        {% else %}
                                            <span class="button small alert">{{ Lang.dons-admin.failed }}</span>
                                        {% endif %}
                                    </td>
                                    <td>{{ don.date }}</td>
                                </tr>
                            {% endfor %}
                            {% if not dons %}
                                <tr>
                                    <td colspan="6" class="empty-donations">
                                        {{ Lang.dons-admin.no-donations }}
                                    </td>
                                </tr>
                            {% endif %}
                        </tbody>
                    </table>
                </div>
            </li>

            <!-- Onglet Configuration -->
            <li class="tab">
                <form method="post" action="{ ROUTER.generate("dons-admin-save-params") }" class="admin-form">
                    <input type="hidden" name="token" value="{{ token }}" />
                    
                    <fieldset>
                        <legend>{{ Lang.dons-admin.public-page }}</legend>
                        <div class="form-group">
                            <label for="pageTitle">{{ Lang.dons-admin.page-title }}</label>
                            <input type="text" name="pageTitle" id="pageTitle" value="{{ pageTitle }}" required />
                        </div>
                        <div class="form-group">
                            <label for="description">{{ Lang.dons-admin.description }}</label>
                            <textarea name="description" id="description" rows="3">{{ description }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="targetAmount">{{ Lang.dons-admin.target-amount }}</label>
                            <input type="number" name="targetAmount" id="targetAmount" value="{{ targetAmount }}" step="0.01" />
                            <small>{{ Lang.dons-admin.target-help }}</small>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" name="showDonorsList" value="1" {% if showDonorsList %}checked{% endif %} />
                                {{ Lang.dons-admin.showDonorsList }}
                            </label>
                            <small>{{ Lang.dons-admin.showDonorsList-help }}</small>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" name="continueAfterTarget" value="1" {% if continueAfterTarget %}checked{% endif %} />
                                {{ Lang.dons-admin.continueAfterTarget }}
                            </label>
                            <small>{{ Lang.dons-admin.continueAfterTarget-help }}</small>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" name="showDonationGraph" value="1" {% if showDonationGraph %}checked{% endif %} />
                                {{ Lang.dons-admin.showDonationGraph }}
                            </label>
                            <small>{{ Lang.dons-admin.showDonationGraph-help }}</small>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>PayPal</legend>
                        <div class="form-group">
                            <label for="paypalClientId">Client ID</label>
                            <input type="text" name="paypalClientId" id="paypalClientId" value="{{ paypalClientId }}" />
                        </div>
                        <div class="form-group">
                            <label for="paypalSecret">Client Secret</label>
                            <input type="password" name="paypalSecret" id="paypalSecret" value="{{ paypalSecret }}" />
                        </div>
                        <div class="form-group">
                            <label for="paypalMode">{{ Lang.dons-admin.mode }}</label>
                            <select name="paypalMode" id="paypalMode">
                                <option value="sandbox" {% if paypalMode == "sandbox" %}selected{% endif %}>Sandbox (Test)</option>
                                <option value="live" {% if paypalMode == "live" %}selected{% endif %}>Live (Production)</option>
                            </select>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Stripe</legend>
                        <div class="form-group">
                            <label for="stripePublishableKey">Publishable Key</label>
                            <input type="text" name="stripePublishableKey" id="stripePublishableKey" value="{{ stripePublishableKey }}" />
                        </div>
                        <div class="form-group">
                            <label for="stripeSecretKey">Secret Key</label>
                            <input type="password" name="stripeSecretKey" id="stripeSecretKey" value="{{ stripeSecretKey }}" />
                        </div>
                        <div class="form-group">
                            <label for="stripeMode">{{ Lang.dons-admin.mode }}</label>
                            <select name="stripeMode" id="stripeMode">
                                <option value="test" {% if stripeMode == "test" %}selected{% endif %}>Test</option>
                                <option value="live" {% if stripeMode == "live" %}selected{% endif %}>Live</option>
                            </select>
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <button type="submit" class="button success">
                            <i class="fa-solid fa-floppy-disk"></i> {{ Lang.core-save }}
                        </button>
                    </div>
                </form>
            </li>
        </ul>
    </div>
</section>

<div class="stats-panel">
    <h3><i class="fa-solid fa-chart-line"></i> {{ Lang.dons-admin.donation-trends }}</h3>
    <canvas id="donationsChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('donationsChart');
if (ctx) {
    fetch('{{ ROUTER.generate("dons-admin-chart-data") }}')
        .then(r => r.json())
        .then(data => {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.date),
                    datasets: [{
                        label: '{{ Lang.dons-admin.daily-donations }}',
                        data: data.map(d => d.amount),
                        borderColor: 'rgb(26, 58, 95)',
                        backgroundColor: 'rgba(26, 58, 95, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {display: false}
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {callback: (v) => v + ' €'}
                        }
                    }
                }
            });
        })
        .catch(e => console.error('Chart error:', e));
}
</script>

<style>
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}
.stat-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 0.5rem;
}
.stat-label {
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
}
.admin-form fieldset {
    margin-bottom: 2rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1.5rem;
}
.admin-form legend {
    font-weight: 700;
    padding: 0 0.75rem;
    color: #1e293b;
}
.form-group {
    margin-bottom: 1.25rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
}
.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="password"],
.form-group textarea,
.form-group select {
    width: 100%;
}
</style>
