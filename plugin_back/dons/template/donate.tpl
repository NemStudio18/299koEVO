<section>
    <header>
        <h1>{{ Lang.dons.name }}</h1>
        {% if description %}
            <p>{{ description }}</p>
        {% endif %}
    </header>
    
    {{ SHOW.displayMsg }}
        
        <div class="dons-stats-summary">
            <div class="dons-stat-item">
                <span class="dons-stat-label">{{ Lang.dons.total-collected }}</span>
                <span class="dons-stat-value">{{ totalAmountFormatted }} €</span>
            </div>
            {% if targetAmount > 0 %}
                <div class="dons-stat-item">
                    <span class="dons-stat-label">{{ Lang.dons.objective }}</span>
                    <span class="dons-stat-value">{{ targetAmountFormatted }} €</span>
                </div>
            {% endif %}
        </div>

        {% if targetAmount > 0 %}
            <div class="dons-progress">
                <div class="dons-progress-bar-container">
                    <div class="dons-progress-bar" style="width: {{ progress }}%"></div>
                </div>
                <div class="dons-progress-info">
                    <span class="dons-progress-percent">{{ progressFormatted }}%</span>
                </div>
            </div>
        {% endif %}
        
        <article>
            {% if goalReached and not continueAfterTarget %}
                <div class="dons-goal-reached">
                    <i class="fa-solid fa-flag-checkered"></i>
                    <p>{{ Lang.dons.goal-reached }}</p>
                </div>
            {% else %}
                <div class="dons-amount-presets">
                    <button type="button" class="preset-btn" onclick="setAmount(10)">10€</button>
                    <button type="button" class="preset-btn" onclick="setAmount(20)">20€</button>
                    <button type="button" class="preset-btn active" onclick="setAmount(50)">50€</button>
                    <button type="button" class="preset-btn" onclick="setAmount(100)">100€</button>
                    <button type="button" class="preset-btn" onclick="toggleCustomAmount()">{{ Lang.dons.amount-placeholder }}</button>
                </div>

                <form id="donation-form" class="dons-form">
                <div class="dons-input-group" id="custom-amount-group">
                        <label for="amount">{{ Lang.dons.amount }}</label>
                        <input type="number" name="amount" id="amount" value="50" min="1" step="0.01" required />
                    </div>
                    
                    <div class="dons-form-grid">
                        <div class="dons-input-group">
                            <label for="firstName">{{ Lang.dons.firstName }}</label>
                            <input type="text" name="firstName" id="firstName" required />
                        </div>
                        <div class="dons-input-group">
                            <label for="lastName">{{ Lang.dons.lastName }}</label>
                            <input type="text" name="lastName" id="lastName" required />
                        </div>
                    </div>
                    
                    <div class="dons-input-group">
                        <label for="email">{{ Lang.dons.email }}</label>
                        <input type="email" name="email" id="email" required />
                    </div>
                    
                    <div class="dons-input-group">
                        <label for="message">{{ Lang.dons.message }} ({{ Lang.dons.optional }})</label>
                        <textarea name="message" id="message" rows="3"></textarea>
                    </div>
                    
                    <label class="dons-checkbox-group">
                        <input type="checkbox" name="anonymous" id="anonymous" />
                        <span>{{ Lang.dons.anonymous }}</span>
                    </label>
                    
                    <div class="dons-gateways">
                        {% if hasPayPal %}
                            <button type="button" class="gateway-btn paypal-btn" onclick="donatePayPal()">
                                <i class="fa-brands fa-paypal"></i> {{ Lang.dons.pay-with-paypal }}
                            </button>
                        {% endif %}
                        {% if hasStripe %}
                            <button type="button" class="gateway-btn stripe-btn" onclick="donateStripe()">
                                <i class="fa-brands fa-stripe"></i> {{ Lang.dons.pay-with-stripe }}
                            </button>
                        {% endif %}
                    </div>
                </form>
            {% endif %}
        </article>

        {% if showDonorsList %}
            <article>
                <h3><i class="fa-solid fa-users"></i> {{ Lang.dons.recent-donors }}</h3>
                <div class="donor-grid">
                    {% for donor in recentDonors %}
                        <div class="donor-item">
                            <span class="donor-name">{{ donor.firstName }} {{ donor.lastName }}</span>
                            <span class="donor-meta">{{ donor.date }} • {{ donor.amountFormatted }} €</span>
                        </div>
                    {% endfor %}
                    {% if not recentDonors %}
                        <p class="no-donors">{{ Lang.dons.no-public-donors }}</p>
                    {% endif %}
                </div>
            </article>
        {% endif %}

        {% if showDonationGraph %}
            <article class="dons-chart-section">
                <h3><i class="fa-solid fa-chart-area"></i> {{ Lang.dons.donation-history }}</h3>
                <canvas id="publicDonationsChart"></canvas>
            </article>
        {% endif %}
</section>

{% if showDonationGraph %}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const publicCtx = document.getElementById('publicDonationsChart');
if (publicCtx) {
    fetch('{{ ROUTER.generate("dons-admin-chart-data") }}')
        .then(r => r.json())
        .then(data => {
            new Chart(publicCtx, {
                type: 'bar',
                data: {
                    labels: data.map(d => new Date(d.date).toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'})),
                    datasets: [{
                        label: '{{ Lang.dons-admin.daily-donations }}',
                        data: data.map(d => d.amount),
                        backgroundColor: 'rgba(26, 58, 95, 0.7)',
                        borderColor: 'rgb(26, 58, 95)',
                        borderWidth: 1
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
{% endif %}

<script>
function setAmount(val) {
    document.getElementById('amount').value = val;
    document.querySelectorAll('.preset-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById('custom-amount-group').style.display = 'none';
}

function toggleCustomAmount() {
    document.getElementById('custom-amount-group').style.display = 'block';
    document.querySelectorAll('.preset-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById('amount').focus();
}

{% if hasPayPal %}
function donatePayPal() {
    const form = document.getElementById('donation-form');
    const formData = new FormData(form);
    
    fetch('{{ paypalCreateUrl }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.approvalUrl) {
            window.location.href = data.approvalUrl;
        } else {
            alert(data.error || '{{ Lang.dons.error }}');
        }
    })
    .catch(error => {
        alert('{{ Lang.dons.error }}');
    });
}
{% endif %}

{% if hasStripe %}
function donateStripe() {
    const form = document.getElementById('donation-form');
    const formData = new FormData(form);
    
    fetch('{{ stripeCreateUrl }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.sessionId) {
            const stripe = Stripe('{{ stripePublishableKey }}');
            stripe.redirectToCheckout({ sessionId: data.sessionId });
        } else {
            alert(data.error || '{{ Lang.dons.error }}');
        }
    })
    .catch(error => {
        alert('{{ Lang.dons.error }}');
    });
}
{% endif %}
</script>
{% if hasStripe %}
<script src="https://js.stripe.com/v3/"></script>
{% endif %}
