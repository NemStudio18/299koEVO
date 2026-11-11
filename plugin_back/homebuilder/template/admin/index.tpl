{# Administration - Gestion des blocs de la page d'accueil #}

<div class="admin-homepage">
    <div class="admin-header">
        <h1>Gestion de la page d'accueil</h1>
        <div class="admin-actions">
            <a href="{{ ROUTER.generate("admin-homebuilder-add") }}" class="btn btn-primary">
                <i class="fa fa-plus"></i> Ajouter un bloc
            </a>
        </div>
    </div>

    {% IF hasBlocks %}
        <div class="blocks-list" id="sortable-blocks">
            {% FOR block IN blocks %}
                <div class="block-item" data-id="{{ block.id }}">
                    <div class="block-header">
                        <div class="block-info">
                            <h3>{{ block.title }}</h3>
                            <span class="block-type">{{ block.typeLabel }}</span>
                            <span class="block-order">Ordre: {{ block.order }}</span>
                        </div>
                        <div class="block-actions">
                            {% IF block.isActive %}
                                <span class="status active">Actif</span>
                            {% ELSE %}
                                <span class="status inactive">Inactif</span>
                            {% ENDIF %}
                            <a href="{{ block.editUrl }}" class="btn btn-sm btn-secondary">
                                <i class="fa fa-edit"></i> Modifier
                            </a>
                            <a href="{{ block.stylesUrl }}" class="btn btn-sm btn-info">
                                <i class="fa fa-paint-brush"></i> Styles
                            </a>
                            <form method="POST" action="{{ block.deleteUrl }}" style="display: inline;">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce bloc ?')">
                                    <i class="fa fa-trash"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="block-content">
                        <p><strong>Contenu :</strong> {{ block.contentPreview }}</p>
                    </div>
                </div>
            {% ENDFOR %}
        </div>
        
        <div class="admin-footer">
            <p><strong>Note :</strong> Glissez-déposez les blocs pour changer leur ordre d'affichage.</p>
        </div>
    {% ELSE %}
        <div class="no-blocks">
            <p>Aucun bloc configuré. <a href="{{ ROUTER.generate("admin-homebuilder-add") }}">Ajoutez votre premier bloc</a>.</p>
        </div>
    {% ENDIF %}
</div>

<style>
.admin-homepage {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.admin-header h1 {
    margin: 0;
    color: #333;
}

.admin-actions {
    display: flex;
    gap: 10px;
}

.blocks-list {
    margin-bottom: 30px;
}

.block-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 15px;
    padding: 20px;
    cursor: move;
    transition: all 0.3s ease;
}

.block-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.block-item.ui-sortable-helper {
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    transform: rotate(5deg);
}

.block-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.block-info h3 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.3em;
}

.block-type {
    display: inline-block;
    background: #007bff;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    margin-right: 10px;
}

.block-order {
    color: #666;
    font-size: 0.9em;
}

.block-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
}

.status.active {
    background: #d4edda;
    color: #155724;
}

.status.inactive {
    background: #f8d7da;
    color: #721c24;
}

.block-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #007bff;
}

.block-content p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

.no-blocks {
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px dashed #ddd;
}

.no-blocks p {
    margin: 0;
    color: #666;
    font-size: 1.1em;
}

.no-blocks a {
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
}

.no-blocks a:hover {
    text-decoration: underline;
}

.admin-footer {
    margin-top: 20px;
    padding: 15px;
    background: #e7f3ff;
    border-radius: 4px;
    border-left: 4px solid #007bff;
}

.admin-footer p {
    margin: 0;
    color: #0056b3;
    font-size: 0.9em;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .block-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .block-actions {
        justify-content: center;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sortableList = document.getElementById('sortable-blocks');
    if (sortableList) {
        new Sortable(sortableList, {
            animation: 150,
            ghostClass: 'ui-sortable-helper',
            onEnd: function(evt) {
                const blocks = [];
                const blockItems = sortableList.querySelectorAll('.block-item');
                
                blockItems.forEach((item, index) => {
                    blocks.push({
                        id: item.dataset.id,
                        order: index + 1
                    });
                });
                
                // Envoyer la nouvelle ordre au serveur
                fetch('{{ ROUTER.generate("admin-homebuilder-reorder") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ blocks: blocks })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Ordre mis à jour avec succès');
                    } else {
                        console.error('Erreur lors de la mise à jour de l\'ordre');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
            }
        });
    }
});
</script> 
