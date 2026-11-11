<section class="qr-poi-admin-stats">
    <header>
        <h1><i class="fa-solid fa-qrcode"></i> Statistiques des QR codes</h1>
        <p>Métriques d'utilisation des QR codes générés</p>
    </header>

    <!-- KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-qrcode"></i></div>
            <div style="font-size: 2rem; font-weight: bold;">{{ totalPoisWithQrCodes }}</div>
            <div style="opacity: 0.9; margin-top: 0.5rem;">QR codes générés</div>
        </div>
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-mobile-screen-button"></i></div>
            <div style="font-size: 2rem; font-weight: bold;">{{ totalScans }}</div>
            <div style="opacity: 0.9; margin-top: 0.5rem;">Total des scans</div>
        </div>
        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-chart-line"></i></div>
            <div style="font-size: 2rem; font-weight: bold;">
                {% IF totalPoisWithQrCodes > 0 %}
                    {{ avgScansPerQr }}
                {% ELSE %}
                    0
                {% ENDIF %}
            </div>
            <div style="opacity: 0.9; margin-top: 0.5rem;">Scans moyens par QR</div>
        </div>
    </div>

    <!-- Graphique évolution -->
    {% IF chartLabels %}
    <section class="module-card" style="margin: 2rem 0;">
        <header><i class="fa-solid fa-chart-bar"></i> Évolution des scans (30 derniers jours)</header>
        <div style="position: relative; height: 400px; margin: 1rem 0;">
            <canvas id="chartScans"></canvas>
        </div>
    </section>
    {% ENDIF %}

    <!-- Top QR codes -->
    <section class="module-card" style="margin: 2rem 0;">
        <header><i class="fa-solid fa-trophy"></i> QR codes les plus scannés</header>
        {% IF poisWithDetails %}
        <table class="table" style="width: 100%; margin-top: 1rem;">
            <thead>
                <tr style="background: #f5f5f5;">
                    <th style="padding: 0.75rem; text-align: left;">POI</th>
                    <th style="padding: 0.75rem; text-align: right;">Scans</th>
                    <th style="padding: 0.75rem; text-align: left;">Premier scan</th>
                    <th style="padding: 0.75rem; text-align: left;">Dernier scan</th>
                    <th style="padding: 0.75rem; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                {% FOR poi IN poisWithDetails %}
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 0.75rem;">
                        <strong>{{ poi.poi_name }}</strong>
                        <br><small style="color: #6c757d;">ID: {{ poi.poi_id }}</small>
                    </td>
                    <td style="padding: 0.75rem; text-align: right; font-weight: bold; font-size: 1.2rem;">
                        {{ poi.scan_count }}
                    </td>
                    <td style="padding: 0.75rem;">
                        <small>{{ poi.first_scan }}</small>
                    </td>
                    <td style="padding: 0.75rem;">
                        <small>{{ poi.last_scan }}</small>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <a href="{{ poi.qr_code_url }}" target="_blank" class="button small" title="Voir le QR code">
                            <i class="fa-solid fa-qrcode"></i>
                        </a>
                        <a href="{{ poi.redirect_url }}" target="_blank" class="button small" title="Tester la redirection">
                            <i class="fa-solid fa-external-link"></i>
                        </a>
                    </td>
                </tr>
                {% ENDFOR %}
            </tbody>
        </table>
        {% ELSE %}
        <p style="text-align: center; padding: 2rem; color: #6c757d;">Aucun QR code scanné pour le moment.</p>
        {% ENDIF %}
    </section>

    <!-- Scans récents -->
    <section class="module-card" style="margin: 2rem 0;">
        <header><i class="fa-solid fa-clock"></i> Scans récents (20 derniers)</header>
        {% IF recentScans %}
        <table class="table" style="width: 100%; margin-top: 1rem;">
            <thead>
                <tr style="background: #f5f5f5;">
                    <th style="padding: 0.75rem; text-align: left;">Date</th>
                    <th style="padding: 0.75rem; text-align: left;">POI ID</th>
                    <th style="padding: 0.75rem; text-align: left;">IP</th>
                    <th style="padding: 0.75rem; text-align: left;">User Agent</th>
                </tr>
            </thead>
            <tbody>
                {% FOR scan IN recentScans %}
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 0.75rem;">
                        <small>{{ scan.scanned_at }}</small>
                    </td>
                    <td style="padding: 0.75rem;">
                        <strong>{{ scan.poi_id }}</strong>
                    </td>
                    <td style="padding: 0.75rem; font-family: monospace; font-size: 0.9em;">
                        {{ scan.ip_address }}
                    </td>
                    <td style="padding: 0.75rem; font-size: 0.85em; color: #6c757d;">
                        {{ scan.user_agent }}
                    </td>
                </tr>
                {% ENDFOR %}
            </tbody>
        </table>
        {% ELSE %}
        <p style="text-align: center; padding: 2rem; color: #6c757d;">Aucun scan récent.</p>
        {% ENDIF %}
    </section>
</section>

{% IF chartLabels %}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.2.1/dist/chart.umd.min.js"></script>
<script>
const ctxScans = document.getElementById('chartScans');
if (ctxScans) {
    new Chart(ctxScans, {
        type: 'line',
        data: {
            labels: [{{ chartLabels }}],
            datasets: [{
                label: 'Scans',
                data: [{{ chartData }}],
                borderColor: 'rgb(102, 126, 234)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
</script>
{% ENDIF %}

<style>
.qr-poi-admin-stats {
    padding: 1rem;
}

.qr-poi-admin-stats header {
    margin-bottom: 2rem;
}

.qr-poi-admin-stats header h1 {
    margin: 0 0 0.5rem 0;
    color: #667eea;
}

.qr-poi-admin-stats header p {
    margin: 0;
    color: #6c757d;
}

.qr-poi-admin-stats .table {
    border-collapse: collapse;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.qr-poi-admin-stats .table thead {
    background: #f8f9fa;
}

.qr-poi-admin-stats .table th {
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.qr-poi-admin-stats .table td {
    padding: 0.75rem;
    border-bottom: 1px solid #dee2e6;
}

.qr-poi-admin-stats .table tbody tr:hover {
    background: #f8f9fa;
}
</style>

