<div class="parrainage-public-dashboard">
    <header class="dashboard-header" style="margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
        <h1><i class="fa-solid fa-handshake-angle"></i> Mon Espace Parrainage</h1>
        <p class="text-muted">Suivez vos filleuls et vos commissions en temps réel.</p>
    </header>

    <?php foreach ($codes as $code): 
        $referralUrl = SITE_URL . '/users/register?ref=' . urlencode($code['code']);
    ?>
        <section class="code-section" style="margin-bottom: 40px; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
            <div style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1; min-width: 280px;">
                    <h2 style="margin:0; font-size: 1.5rem;">Code : <span style="color: var(--primary-color); background: #f0f7ff; padding: 5px 15px; border-radius: 6px; border: 1px dashed var(--primary-color);"><?= htmlspecialchars($code['code']) ?></span></h2>
                    <p style="margin:10px 0 20px 0; color: #666;">Partagez ce code ou votre lien direct pour parrainer de nouveaux membres et toucher <strong><?= $code['commission_percent'] ?>%</strong> de commission.</p>
                    <div class="share-link-container" style="background: #f9f9f9; padding: 10px 15px; border-radius: 8px; border: 1px solid #eee; display: flex; align-items: center; gap: 10px;">
                        <input type="text" value="<?= $referralUrl ?>" id="ref_link_<?= $code['id'] ?>" readonly style="flex:1; border:none; background:transparent; font-size:0.9rem; color:#555;">
                        <button class="button small" onclick="copyToClipboard('ref_link_<?= $code['id'] ?>')">
                            <i class="fa-solid fa-copy"></i> Copier
                        </button>
                    </div>
                </div>
                
                <div style="text-align:center; background: #fff; padding: 10px; border: 1px solid #eee; border-radius: 8px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode($referralUrl) ?>" alt="QR Code Parrainage" style="display:block; margin: 0 auto 5px;">
                    <span style="font-size: 0.7rem; color: #999;">Scan pour s'inscrire</span>
                </div>

                <div style="text-align:right; min-width: 150px;">
                    <div style="font-size: 0.9em; color: #7f8c8d;">Gains mensuels estimés</div>
                    <div style="font-size: 2.2em; font-weight: bold; color: #27ae60;"><?= number_format($code['total_estimated_monthly'], 2, ',', ' ') ?> €</div>
                </div>
            </div>

            <div class="referrals-list">
                <h3>Mes Filleuls (<?= count($code['referrals']) ?>)</h3>
                <table style="width:100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="text-align:left; border-bottom: 1px solid #eee;">
                            <th style="padding:10px;">Filleul</th>
                            <th style="padding:10px;">Abonnement</th>
                            <th style="padding:10px;">Gain mensuel estimé</th>
                            <th style="padding:10px;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($code['referrals'] as $ref): ?>
                            <tr style="border-bottom: 1px solid #f9f9f9;">
                                <td style="padding:10px;">
                                    <strong><?= htmlspecialchars($ref['referred_username']) ?></strong>
                                    <div style="font-size:0.8em; color:#999;"><?= htmlspecialchars($ref['referred_email']) ?></div>
                                </td>
                                <td style="padding:10px;">
                                    <?php if ($ref['has_subscription']): ?>
                                        <span style="color: #27ae60;"><i class="fa-solid fa-check-circle"></i> Actif (<?= ucfirst($ref['plan_type']) ?>)</span>
                                    <?php else: ?>
                                        <span style="color: #95a5a6;"><i class="fa-solid fa-clock"></i> Inactif</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:10px; font-weight:bold;">
                                    <?= number_format($ref['estimated_monthly_gain'], 2, ',', ' ') ?> €
                                </td>
                                <td style="padding:10px; color:#999; font-size:0.9em;">
                                    <?= date('d/m/Y', strtotime($ref['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($code['referrals'])): ?>
                            <tr>
                                <td colspan="4" style="padding:20px; text-align:center; color:#bdc3c7;">
                                    Aucun filleul enregistré avec ce code pour le moment.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<script>
function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices
    navigator.clipboard.writeText(copyText.value);
    
    if (typeof Toastify === "function") {
        Toastify({
            text: "Lien copié dans le presse-papier !",
            duration: 3000,
            className: "success",
            gravity: "bottom",
            position: "right"
        }).showToast();
    } else {
        alert("Lien copié !");
    }
}
</script>

<style>
.parrainage-public-dashboard h1 { color: #2c3e50; font-size: 24px; margin: 0; }
.parrainage-public-dashboard h3 { font-size: 18px; border-left: 4px solid var(--primary-color); padding-left: 10px; margin-top: 25px; }
.badge { background: var(--primary-color); color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
.share-link-container input:focus { outline: none; }
</style>
