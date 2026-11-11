<div class="tabs-container parrainage-admin-tabs">
    <ul class="tabs-header">
        <li class="<?= ($activeTab === 'overview') ? 'default-tab' : '' ?>">
            <i class="fa-solid fa-chart-line"></i> Vue d'ensemble
        </li>
        <li class="<?= ($activeTab === 'codes') ? 'default-tab' : '' ?>">
            <i class="fa-solid fa-ticket"></i> Codes d'affiliation
        </li>
        <li class="<?= ($activeTab === 'config') ? 'default-tab' : '' ?>">
            <i class="fa-solid fa-gear"></i> Configuration
        </li>
    </ul>

    <ul class="tabs">
        <!-- Tab 1: Overview -->
        <li class="tab">
            <section class="content">
                <div class="admin-row">
                    <div class="admin-col-6">
                        <div class="admin-card stats-card">
                            <div class="admin-card-body text-center">
                                <h2 class="font-large"><?= $stats['total_codes'] ?></h2>
                                <p class="color-muted">Codes créés</p>
                            </div>
                        </div>
                    </div>
                    <div class="admin-col-6">
                        <div class="admin-card stats-card">
                            <div class="admin-card-body text-center">
                                <h2 class="font-large"><?= $stats['total_referrals'] ?></h2>
                                <p class="color-muted">Utilisations totales</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </li>

        <!-- Tab 2: Affiliate Codes -->
        <li class="tab">
            <?php if (isset($msg) && $activeTab === 'codes'): ?>
                <div class="alert alert-<?= $msgType ?>"><?= $msg ?></div>
            <?php endif; ?>

            <div class="admin-row">
                <!-- Formulaire d'ajout -->
                <div class="admin-col-4">
                    <div class="admin-card">
                        <div class="admin-card-header">Ajouter un nouveau code</div>
                        <div class="admin-card-body">
                            <form action="?tab=codes" method="POST">
                                <input type="hidden" name="action" value="add_code">
                                <input type="hidden" name="token" value="<?= $token ?>">
                                <div class="form-group">
                                    <label>Libellé du Code</label>
                                    <input type="text" name="code" class="form-control" placeholder="ex: PARTENAIRE2024" required>
                                </div>
                                <div class="form-group">
                                    <label>Commission (%)</label>
                                    <input type="number" step="0.5" name="commission" class="form-control" value="10">
                                </div>
                                <div class="form-group">
                                    <label>ID Utilisateur propriétaire (Optionnel)</label>
                                    <input type="number" name="user_id" class="form-control" placeholder="ex: 42">
                                    <small>Lie le code à un compte existant.</small>
                                </div>
                                <div class="form-group">
                                    <label>Email propriétaire (Optionnel)</label>
                                    <input type="email" name="email" class="form-control" placeholder="ex: partner@example.com">
                                    <small>Alternative si pas encore de compte.</small>
                                </div>
                                <button type="submit" class="admin-btn admin-btn-primary" style="margin-top:10px;">Enregistrer</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="admin-card" style="margin-top:20px;">
                        <div class="admin-card-header">Actions</div>
                        <div class="admin-card-body">
                            <form action="?tab=codes" method="POST">
                                <input type="hidden" name="action" value="export_json">
                                <input type="hidden" name="token" value="<?= $token ?>">
                                <button type="submit" class="admin-btn admin-btn-secondary">
                                    <i class="fa-solid fa-download"></i> Exporter en JSON
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Liste des codes existants -->
                <div class="admin-col-8">
                    <div class="admin-card">
                        <div class="admin-card-header">Codes actifs</div>
                        <div class="admin-card-body">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Propriétaire</th>
                                        <th>Commission</th>
                                        <th>Utilisations</th>
                                        <th>Créé le</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($codes as $c): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($c['code']) ?></strong></td>
                                            <td>
                                                <?php if ($c['owner_username']): ?>
                                                    <?= htmlspecialchars($c['owner_username']) ?> (ID: <?= $c['user_id'] ?>)
                                                <?php elseif ($c['email']): ?>
                                                    <?= htmlspecialchars($c['email']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Générique</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $c['commission_percent'] ?>%</td>
                                            <td><span class="badge"><?= $c['usage_count'] ?></span></td>
                                            <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($codes)): ?>
                                        <tr><td colspan="5" style="text-align:center;">Aucun code défini.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </li>

        <!-- Tab 3: Configuration -->
        <li class="tab">
            <section class="content">
                <div class="admin-card">
                    <div class="admin-card-body color-muted italic">
                        Les paramètres globaux du plugin seront bientôt disponibles ici.
                    </div>
                </div>
            </section>
        </li>
    </ul>
</div>

<style>
.parrainage-admin-tabs .stats-card h2 {
    font-size: 2.5rem;
    margin: 0;
    color: var(--primary-color);
}
.parrainage-admin-tabs .admin-card-header {
    font-weight: bold;
    border-bottom: 1px solid var(--form-border-color);
    padding-bottom: 10px;
    margin-bottom: 15px;
}
</style>
