<section class="module-card" id="session-reporter-admin">
    <header>
        <div class="d-flex justify-content-between align-items-center w-100">
            <span><i class="fa-solid fa-bug-slash me-2"></i> SessionReporter : Observabilité & Debug</span>
            <a href="<?php echo \Core\Router\Router::getInstance()->generate('admin-sessionreporter-set-debug-token'); ?>" class="btn btn-sm btn-outline-danger shadow-sm" title="Active le mode debug pour votre session (1h)">
                <i class="fa-solid fa-shield-halved me-1"></i> Activer Debug Mode (Self)
            </a>
        </div>
    </header>

    <div class="tabs-container">
        <ul class="tabs-header">
            <li class="default-tab">
                <i class="fa-solid fa-list-ul me-1"></i> Incidents
            </li>
            <li>
                <i class="fa-solid fa-map-location-dot me-1"></i> Heatmap
            </li>
            <li>
                <i class="fa-solid fa-key me-1"></i> Codes Bêta
            </li>
        </ul>

        <ul class="tabs">
            <!-- Onglet 1: Incidents -->
            <li class="tab">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Sévérité</th>
                                <th>Type / Message</th>
                                <th>Occurrences</th>
                                <th>Dernière vue</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($groups as $g): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge rounded-pill <?php echo $g['severity_score'] > 50 ? 'bg-danger' : ($g['severity_score'] > 20 ? 'bg-warning text-dark' : 'bg-info'); ?>">
                                        Score: <?php echo round($g['severity_score'], 1); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($g['type']); ?></div>
                                    <div class="text-muted small text-truncate" style="max-width: 400px;">
                                        <?php echo htmlspecialchars($g['message']); ?>
                                    </div>
                                    <div class="text-primary x-small mt-1">
                                        <i class="fa-solid fa-file-code me-1"></i> <?php echo htmlspecialchars($g['file']); ?>:<?php echo $g['line']; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $g['occurrences']; ?></span>
                                </td>
                                <td>
                                    <span class="text-muted small"><?php echo $g['last_seen']; ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="button small shadow-sm" onclick="SR_toggleDetails('<?php echo $g['fingerprint']; ?>')">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr id="details-<?php echo $g['fingerprint']; ?>" class="d-none bg-light">
                                <td colspan="5" class="p-4">
                                    <div class="bg-white p-3 rounded border shadow-sm">
                                        <h6 class="border-bottom pb-2 mb-3">Détails techniques (Dernier Snapshot)</h6>
                                        <pre class="bg-dark text-light p-3 rounded overflow-auto mb-0" style="max-height: 400px; font-size: 0.85rem;"><code><?php echo htmlspecialchars(json_encode(json_decode($g['sample_content']), JSON_PRETTY_PRINT)); ?></code></pre>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($groups)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fa-solid fa-circle-check text-success fa-3x mb-3"></i>
                                    <p class="text-muted">Aucun incident détecté pour le moment. Félicitations !</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </li>

            <!-- Onglet 2: Heatmap -->
            <li class="tab">
                <div class="p-3">
                    <h5 class="mb-3">Analyse des Routes Problématiques</h5>
                    <p class="text-muted small mb-4">Les routes sont normalisées (ex: <code>/article/42</code> devient <code>/article/{id}</code>) pour identifier les points chauds.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Route (Normalisée)</th>
                                    <th>Volume d'événements</th>
                                    <th class="text-end pe-4">Intensité</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($heatmap as $h): ?>
                                <tr>
                                    <td class="ps-4">
                                        <code class="text-primary fw-bold"><?php echo htmlspecialchars($h['uri']); ?></code>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $h['count']; ?> événements</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php 
                                            $intensity = min(100, ($h['count'] / 50) * 100); 
                                            $color = $intensity > 80 ? 'bg-danger' : ($intensity > 40 ? 'bg-warning' : 'bg-success');
                                        ?>
                                        <div class="progress shadow-sm" style="height: 12px; width: 120px; margin-left: auto; border-radius: 6px;">
                                            <div class="progress-bar <?php echo $color; ?>" role="progressbar" style="width: <?php echo $intensity; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($heatmap)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">Aucune donnée de heatmap disponible.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </li>

            <!-- Onglet 3: Codes Bêta -->
            <li class="tab">
                <div class="row p-3">
                    <div class="col-lg-4 mb-4">
                        <div class="card border-0 shadow-sm bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Générer un code</h5>
                                <p class="text-muted small">Chaque code permet une inscription unique avec activation automatique de l'observabilité.</p>
                                <form action="<?php echo $generateUrl; ?>" method="POST">
                                    <button type="submit" class="button success w-100 py-2">
                                        <i class="fa-solid fa-plus-circle me-1"></i> Nouveau Code Invitation
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Code</th>
                                        <th>Statut</th>
                                        <th>Créé le</th>
                                        <th class="text-end pe-4">Utilisé par</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($codes as $c): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <code class="bg-white border px-2 py-1 rounded text-primary fw-bold" style="font-size: 1rem;">
                                                <?php echo htmlspecialchars($c['code']); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <?php if($c['used_at']): ?>
                                                <span class="badge bg-secondary">Utilisé</span>
                                            <?php elseif(strtotime($c['expires_at']) < time()): ?>
                                                <span class="badge bg-danger">Expiré</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Valide</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-muted small"><?php echo $c['created_at']; ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if($c['used_by_user_id']): ?>
                                                <span class="text-dark small">Utilisateur #<?php echo $c['used_by_user_id']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">--</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</section>

<script>
function SR_toggleDetails(fp) {
    const el = document.getElementById('details-' + fp);
    el.classList.toggle('d-none');
}
</script>

<style>
#session-reporter-admin .x-small { font-size: 0.75rem; }
#session-reporter-admin .table th { font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
#session-reporter-admin pre { white-space: pre-wrap; word-break: break-all; }
#session-reporter-admin .tabs-header li { font-weight: 500; font-size: 0.95rem; }
#session-reporter-admin .progress { background-color: #e9ecef; }
</style>
