<div class="tabs-container forum-admin-tabs">
    <ul class="tabs-header">
        <li class="default-tab"><i class="fa-solid fa-chart-simple"></i> {{Lang.forum.dashboard}}</li>
        <li><i class="fa-solid fa-folder-tree"></i> {{Lang.forum.categories}}</li>
        <li><i class="fa-solid fa-plus"></i> {{Lang.forum.category.add}}</li>
        <li><i class="fa-regular fa-comments"></i> {{Lang.forum.moderation}}</li>
        <li><i class="fa-solid fa-user-shield"></i> Modérateurs</li>
        <li><i class="fa-solid fa-cogs"></i> Paramètres</li>
    </ul>
    <ul class="tabs">
        <li class="tab">
            <div class="forum-admin-stats">
                <article class="stat-card">
                    <span>{{Lang.forum.stats.categories}}</span>
                    <strong>{{stats.categoriesCount}}</strong>
                </article>
                <article class="stat-card">
                    <span>{{Lang.forum.stats.topics}}</span>
                    <strong>{{stats.topicsCount}}</strong>
                </article>
                <article class="stat-card">
                    <span>{{Lang.forum.stats.posts}}</span>
                    <strong>{{stats.postsCount}}</strong>
                </article>
                <article class="stat-card">
                    <span>{{Lang.forum.stats.pending}}</span>
                    <strong>{{stats.pendingCount}}</strong>
                </article>
            </div>
        </li>
        <li class="tab">
            {% IF categories %}
            <table class="table">
                <thead>
                    <tr>
                        <th>{{Lang.forum.categories}}</th>
                        <th>{{Lang.forum.category.description}}</th>
                        <th>Type</th>
                        <th>{{Lang.forum.topics}}</th>
                        <th>{{Lang.forum.posts}}</th>
                        <th>{{Lang.actions}}</th>
                    </tr>
                </thead>
                <tbody>
                    {% FOR category IN categories %}
                    <tr class="{% IF category.is_subcategory %}bg-muted{% ENDIF %}">
                        <td>
                            {% IF category.is_subcategory %}
                            <span class="subcategory-arrow">
                                <i class="fa-solid fa-arrow-turn-down-right"></i> 
                            </span>
                            {% ENDIF %}
                            {% IF category.icon %}
                            <i class="{{category.icon}} mr-small" style="color: {% IF category.color %}{{category.color}}{% ELSE %}#667eea{% ENDIF %};"></i>
                            {% ENDIF %}
                            <strong>{{category.name}}</strong>
                            {% IF category.is_subcategory %}
                            <span class="color-muted font-small ml-small">(Sous-forum)</span>
                            {% ENDIF %}
                        </td>
                        <td>{{category.description}}</td>
                        <td>
                            {% IF category.is_subcategory %}
                            <span class="color-muted">Sous-forum</span>
                            {% ELSE %}
                            <span class="color-success">Catégorie</span>
                            {% ENDIF %}
                        </td>
                        <td>{{category.topic_count}}</td>
                        <td>{{category.post_count}}</td>
                        <td class="forum-admin-actions-inline">
                            <a class="button small" href="{{category.editUrl}}">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{category.deleteUrl}}" onsubmit="return confirm('{{Lang.forum.category.delete-confirm}}');">
                                <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                <button type="submit" class="button alert small">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    {% IF category.subcategories %}
                        {% FOR subcat IN category.subcategories %}
                        <tr class="bg-muted">
                            <td>
                                <div class="max-width-300">
                                    <i class="fa-solid fa-arrow-turn-down-right"></i> 
                                </div>
                                <strong>{{subcat.name}}</strong>
                                <span class="color-muted font-small ml-small">(Sous-forum de {{category.name}})</span>
                            </td>
                            <td>{{subcat.description}}</td>
                            <td><span style="color: #6c757d;">Sous-forum</span></td>
                            <td>{{subcat.topic_count}}</td>
                            <td>{{subcat.post_count}}</td>
                            <td class="forum-admin-actions-inline">
                                <a class="button small" href="{{subcat.editUrl}}">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                                <form method="POST" action="{{subcat.deleteUrl}}" onsubmit="return confirm('{{Lang.forum.category.delete-confirm}}');">
                                    <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                    <button type="submit" class="button alert small">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        {% ENDFOR %}
                    {% ENDIF %}
                    {% ENDFOR %}
                </tbody>
            </table>
            {% ELSE %}
                <p>{{Lang.forum.categories-empty}}</p>
            {% ENDIF %}
        </li>
        <li class="tab">
            <form class="form" method="POST" action="{{categoryAddUrl}}">
                <input type="hidden" name="_csrf" value="{{csrfToken}}">
                <label>{{Lang.forum.category.name}}</label>
                <input type="text" name="name" required>
                <label>{{Lang.forum.category.slug}}</label>
                <input type="text" name="slug" placeholder="{{Lang.forum.category.slug-help}}">
                <label>{{Lang.forum.category.description}}</label>
                <textarea name="description" rows="3"></textarea>
                <label>{{Lang.forum.category.parent}}</label>
                <select name="parent_id">
                    <option value="0">-- Catégorie principale (pas de parent) --</option>
                    {% FOR parentCat IN parentCategories %}
                    <option value="{{parentCat.id}}">{{parentCat.name}}</option>
                    {% ENDFOR %}
                </select>
                <small>Laissez "Catégorie principale" pour créer une catégorie de niveau 1, ou sélectionnez une catégorie pour créer un sous-forum.</small>
                <label>{{Lang.forum.category.icon}}</label>
                <input type="text" name="icon" placeholder="fa-solid fa-comments">
                <small>Icône FontAwesome (ex: fa-solid fa-comments)</small>
                <label>{{Lang.forum.category.color}}</label>
                <input type="color" name="color" value="#667eea">
                <small>Couleur de l'icône et des bordures</small>
                <label>{{Lang.forum.category.order}}</label>
                <input type="number" name="order_index" value="0" min="0">
                <div class="form-actions">
                    <button type="submit" class="button primary">{{Lang.forum.category.save}}</button>
                </div>
            </form>
        </li>
        <li class="tab">
            {% IF pendingPosts %}
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{Lang.forum.topics}}</th>
                        <th>{{Lang.forum.author}}</th>
                        <th>{{Lang.forum.message}}</th>
                        <th>{{Lang.forum.last-post}}</th>
                        <th>{{Lang.actions}}</th>
                    </tr>
                </thead>
                <tbody>
                    {% FOR post IN pendingPosts %}
                    <tr>
                        <td>{{post.id}}</td>
                        <td>
                            {% IF post.topic_title %}
                                <strong>{{post.topic_title}}</strong><br>
                            {% ELSE %}
                                <strong>Topic #{{post.topic_id}}</strong><br>
                            {% ENDIF %}
                            <small>ID {{post.topic_id}}</small>
                        </td>
                        <td>{{post.author_name}}</td>
                        <td>{{post.content}}</td>
                        <td>{{post.created_at_formatted}}</td>
                        <td class="forum-admin-actions-inline">
                            <form method="POST" action="{{moderationApproveUrl}}">
                                <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                <input type="hidden" name="post_id" value="{{post.id}}">
                                <button type="submit" class="button success small">{{Lang.forum.approve}}</button>
                            </form>
                            <form method="POST" action="{{moderationDeleteUrl}}" onsubmit="return confirm('{{Lang.forum.delete-confirm}}');">
                                <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                <input type="hidden" name="post_id" value="{{post.id}}">
                                <button type="submit" class="button alert small">{{Lang.forum.delete}}</button>
                            </form>
                        </td>
                    </tr>
                    {% ENDFOR %}
                </tbody>
            </table>
            {% ELSE %}
                <p>{{Lang.forum.no-posts}}</p>
            {% ENDIF %}
        </li>
        <li class="tab">
            <h2 class="mb-large"><i class="fa-solid fa-user-shield"></i> Gestion des modérateurs</h2>
            <p class="mb-large color-muted">Les modérateurs peuvent verrouiller/déverrouiller les sujets, épingler/désépingler, approuver/supprimer les messages et modérer le forum.</p>
            <p class="mb-large"><strong>Note :</strong> Pour ajouter ou retirer le statut de modérateur à un utilisateur, allez dans <a href="{{usersAdminUrl}}" class="color-primary-admin">Administration > Utilisateurs</a> et modifiez les permissions de l'utilisateur. Cochez la permission "Modérateur du forum" pour lui donner ce statut.</p>
            {% IF moderators %}
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom d'utilisateur</th>
                        <th>Email</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    {% FOR moderator IN moderators %}
                    <tr>
                        <td>{{moderator.id}}</td>
                        <td><strong>{{moderator.username}}</strong></td>
                        <td>{{moderator.email}}</td>
                        <td>
                            {% IF moderator.status == "active" %}
                            <span class="color-success">Actif</span>
                            {% ELSE %}
                            <span class="color-alert">{{moderator.status}}</span>
                            {% ENDIF %}
                        </td>
                    </tr>
                    {% ENDFOR %}
                </tbody>
            </table>
            {% ELSE %}
            <p class="color-muted">Aucun modérateur configuré pour le moment.</p>
            {% ENDIF %}
        </li>
        <li class="tab">
            <h2 class="mb-large"><i class="fa-solid fa-cogs"></i> Configuration du Forum</h2>
            
            <form class="form" method="POST" action="{{configSaveUrl}}">
                <input type="hidden" name="_csrf" value="{{csrfToken}}">
                
                <div class="row">
                    <div class="col-md-12">
                        <section class="card mb-medium p-medium">
                            <h3 class="mb-medium">Affichage</h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label-bold">Libellé Accueil du Forum (Fil d'ariane)</label>
                                    <input type="text" name="forum_breadcrumb_label" value="{{config.forum_breadcrumb_label}}" placeholder="Défaut : Forum">
                                    <small class="form-text text-muted mb-medium">Texte affiché dans le fil d'ariane. (Défaut : Forum)</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-bold">Libellé En-tête des tableaux</label>
                                    <input type="text" name="forum_table_header_label" value="{{config.forum_table_header_label}}" placeholder="Défaut : Forum">
                                    <small class="form-text text-muted mb-medium">Texte affiché dans les en-têtes de colonnes (Index, Catégorie). (Défaut : Forum)</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label-bold">Sujets par page</label>
                                    <input type="number" name="topicsPerPage" value="{{config.topicsPerPage}}" min="5" max="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-bold">Messages par page</label>
                                    <input type="number" name="postsPerPage" value="{{config.postsPerPage}}" min="5" max="100">
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
                
                <div class="form-actions mt-medium">
                    <button type="submit" class="button primary">Enregistrer la configuration</button>
                </div>
            </form>
        </li>
    </ul>
</div>

