<section class="overflow-auto">
    <header><i class="fa-solid fa-home"></i> Gestion de la page d'accueil</header>

    <div style="margin-bottom: 16px;">
        <a href="{{ ROUTER.generate('admin-homebuilder-add') }}" class="button">
            <i class="fa-solid fa-plus"></i> Ajouter un bloc
        </a>
    </div>

    {% IF hasBlocks %}
        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Type</th>
                    <th>Ordre</th>
                    <th>Statut</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="sortable-blocks">
                {% FOR block IN blocks %}
                <tr class="block-row" data-id="{{ block.id }}" style="cursor: move;">
                    <td>
                        <strong>{{ block.title }}</strong>
                    </td>
                    <td>
                        <span class="button small secondary" style="cursor: default; font-size: 0.75rem; padding: 2px 8px;">{{ block.typeLabel }}</span>
                    </td>
                    <td>{{ block.order }}</td>
                    <td>
                        {% IF block.isActive %}
                            <span style="color: var(--success-color); font-weight: 600;"><i class="fa-solid fa-circle-check"></i> Actif</span>
                        {% ELSE %}
                            <span style="color: var(--adm-text-muted);"><i class="fa-regular fa-circle-xmark"></i> Inactif</span>
                        {% ENDIF %}
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ block.editUrl }}" class="button small" title="Modifier">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <a href="{{ block.stylesUrl }}" class="button small secondary" title="Styles">
                            <i class="fa-solid fa-palette"></i>
                        </a>
                        <form method="POST" action="{{ block.deleteUrl }}" style="display: inline;">
                            <button type="submit" class="button small alert" onclick="return confirm('Supprimer ce bloc ?')" title="Supprimer">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                {% ENDFOR %}
            </tbody>
        </table>

        <p style="margin-top: 12px; color: var(--adm-text-muted); font-size: 0.85rem;">
            <i class="fa-solid fa-grip-lines"></i> Glissez-déposez les lignes pour modifier l'ordre d'affichage des blocs.
        </p>
    {% ELSE %}
        <div style="text-align: center; padding: 40px; background: var(--evo-bg); border-radius: 10px; border: 2px dashed var(--adm-border);">
            <p style="color: var(--adm-text-muted); font-size: 1rem; margin: 0 0 12px;">Aucun bloc configuré pour le moment.</p>
            <a href="{{ ROUTER.generate('admin-homebuilder-add') }}" class="button">
                <i class="fa-solid fa-plus"></i> Créer le premier bloc
            </a>
        </div>
    {% ENDIF %}
</section>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('sortable-blocks');
    if (tbody) {
        new Sortable(tbody, {
            animation: 150,
            handle: '.block-row',
            onEnd: function() {
                const blocks = [];
                tbody.querySelectorAll('.block-row').forEach(function(row, index) {
                    blocks.push({ id: row.dataset.id, order: index + 1 });
                });
                fetch('{{ ROUTER.generate("admin-homebuilder-reorder") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ blocks: blocks })
                })
                .then(r => r.json())
                .then(d => { if (!d.success) console.error('Erreur réordonnancement blocs'); })
                .catch(e => console.error('Erreur:', e));
            }
        });
    }
});
</script>
