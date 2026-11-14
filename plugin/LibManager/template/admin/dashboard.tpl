<section class="overflow-auto">
    <header><i class="fa-solid fa-cubes"></i> Gestion des Librairies externes</header>

    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
        <button type="button" class="button" id="btn-check-all" onclick="checkAll(false)">
            <i class="fa-solid fa-rotate"></i> Vérifier les versions (Cache 1h)
        </button>
        <button type="button" class="button secondary" id="btn-force-check" onclick="checkAll(true)">
            <i class="fa-solid fa-bolt"></i> Forcer la vérification GitHub
        </button>
        <a href="{ ROUTER.generate('libmanager-log') }" class="button secondary">
            <i class="fa-solid fa-file-lines"></i> Journal des mises à jour
        </a>
    </div>

    <div id="lm-status" style="display:none; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-weight: 500;"></div>

    <table class="small">
        <thead>
            <tr>
                <th>Librairie</th>
                <th>Type</th>
                <th>Emplacement</th>
                <th>Version installée</th>
                <th>Dernière version</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            {% FOR key,lib IN libs %}
            <tr id="lib-row-{{ key }}">
                <td>
                    <strong>{{ lib.name }}</strong><br>
                    <small style="color: var(--adm-text-muted);">{{ lib.description }}</small>
                </td>
                <td>
                    <span class="button small secondary" style="cursor: default; text-transform: uppercase; font-size: 0.72rem; padding: 2px 8px;">{{ lib.type }}</span>
                </td>
                <td>
                    <code style="font-size: 0.8rem; background: var(--evo-bg); padding: 3px 6px; border-radius: 4px;">{{ lib.install_path }}</code>
                </td>
                <td class="version-current">
                    <strong>{{ lib.current_version }}</strong>
                    {% IF not lib.is_installed %}
                        <br><span style="color: var(--alert-color); font-size: 0.75rem;">(non détecté)</span>
                    {% ENDIF %}
                </td>
                <td class="version-latest">
                    <span class="muted" style="color: var(--adm-text-muted);">—</span>
                </td>
                <td class="lib-action" style="text-align: right;">
                    <span class="muted" style="color: var(--adm-text-muted); font-size: 0.85rem;">En attente de vérification</span>
                </td>
            </tr>
            {% ENDFOR %}
        </tbody>
    </table>
</section>

<script>
async function checkAll(force = false) {
    const btn = force ? document.getElementById('btn-force-check') : document.getElementById('btn-check-all');
    const status = document.getElementById('lm-status');
    const originalHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Vérification en cours...';

    status.style.display = 'block';
    status.style.background = 'var(--evo-info-bg)';
    status.style.color = 'var(--evo-info)';
    status.style.border = '1px solid var(--evo-info-border)';
    status.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Interrogation des versions (en parallèle)...';

    try {
        const formData = new FormData();
        if (force) formData.append('force', '1');

        const res = await fetch('{ ROUTER.generate("libmanager-check") }', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (!data.success) {
            throw new Error(data.message || 'Erreur lors du contrôle des versions.');
        }

        let outdatedCount = 0;
        let rateLimitCount = 0;

        for (const [key, lib] of Object.entries(data.libs)) {
            const row = document.getElementById('lib-row-' + key);
            if (!row) continue;

            const latestCell = row.querySelector('.version-latest');
            const actionCell = row.querySelector('.lib-action');

            if (lib.rate_limited) {
                rateLimitCount++;
                latestCell.innerHTML = '<span style="color: var(--warning-color); font-size:0.85rem;">Quota GitHub</span>';
                actionCell.innerHTML = '<span style="color: var(--adm-text-muted); font-size:0.85rem;">Réessayer plus tard</span>';
                continue;
            }

            if (lib.error) {
                latestCell.innerHTML = '<span style="color: var(--adm-text-muted);">N/A</span>';
                actionCell.innerHTML = '<span style="color: var(--adm-text-muted); font-size:0.85rem;">Indisponible</span>';
                continue;
            }

            if (lib.outdated) {
                outdatedCount++;
                latestCell.innerHTML = `<strong style="color: var(--warning-color);">${lib.latest}</strong> <span style="font-size:0.75rem;">▲</span>`;
                actionCell.innerHTML = `
                    <button type="button" class="button small" onclick="updateLib('${key}', '${lib.latest}')">
                        <i class="fa-solid fa-download"></i> Mettre à jour (v${lib.latest})
                    </button>
                `;
            } else {
                latestCell.innerHTML = `<strong style="color: var(--success-color);">${lib.latest}</strong> <i class="fa-solid fa-check" style="color: var(--success-color); font-size: 0.8rem;"></i>`;
                actionCell.innerHTML = '<span style="color: var(--success-color); font-weight: 600; font-size: 0.85rem;"><i class="fa-solid fa-circle-check"></i> À jour</span>';
            }
        }

        if (rateLimitCount > 0) {
            status.style.background = 'var(--evo-warning-bg)';
            status.style.color = 'var(--evo-warning)';
            status.style.border = '1px solid var(--evo-warning-border)';
            status.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> Limite de requêtes GitHub atteinte pour certaines librairies. Utilisez le cache ou réessayez dans quelques minutes.`;
        } else if (outdatedCount > 0) {
            status.style.background = 'var(--evo-warning-bg)';
            status.style.color = 'var(--evo-warning)';
            status.style.border = '1px solid var(--evo-warning-border)';
            status.innerHTML = `<i class="fa-solid fa-circle-info"></i> <strong>${outdatedCount}</strong> bibliothèque(s) peuvent être mises à jour.`;
        } else {
            status.style.background = 'var(--evo-success-bg)';
            status.style.color = 'var(--evo-success)';
            status.style.border = '1px solid var(--evo-success-border)';
            status.innerHTML = `<i class="fa-solid fa-circle-check"></i> Toutes les bibliothèques vérifiées sont à jour ! (${data.time})`;
        }

    } catch (e) {
        status.style.background = 'var(--evo-danger-bg)';
        status.style.color = 'var(--evo-danger)';
        status.style.border = '1px solid var(--evo-danger-border)';
        status.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> ${e.message}`;
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

async function updateLib(key, targetVersion) {
    if (!confirm(`Confirmer la mise à jour de cette bibliothèque vers la version v${targetVersion} ? Un backup automatique sera créé.`)) {
        return;
    }

    const row = document.getElementById('lib-row-' + key);
    const actionCell = row ? row.querySelector('.lib-action') : null;
    const oldActionHtml = actionCell ? actionCell.innerHTML : '';

    if (actionCell) {
        actionCell.innerHTML = '<span style="color: var(--primary-color);"><i class="fa-solid fa-spinner fa-spin"></i> Mise à jour...</span>';
    }

    try {
        const formData = new FormData();
        formData.append('lib', key);

        const res = await fetch('{ ROUTER.generate("libmanager-update") }', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (!data.success) {
            throw new Error(data.message || 'Échec de la mise à jour.');
        }

        if (row) {
            const currentCell = row.querySelector('.version-current');
            const latestCell = row.querySelector('.version-latest');
            if (currentCell) currentCell.innerHTML = `<strong>${targetVersion}</strong>`;
            if (latestCell) latestCell.innerHTML = `<strong style="color: var(--success-color);">${targetVersion}</strong> <i class="fa-solid fa-check" style="color: var(--success-color);"></i>`;
            if (actionCell) actionCell.innerHTML = '<span style="color: var(--success-color); font-weight: 600;"><i class="fa-solid fa-circle-check"></i> Mis à jour ✓</span>';
        }

        alert(data.message || 'Mise à jour réussie !');

    } catch (e) {
        alert('Erreur: ' + e.message);
        if (actionCell) actionCell.innerHTML = oldActionHtml;
    }
}
</script>
