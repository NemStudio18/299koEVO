<section class="module-card forum-admin-module">
    <header><i class="fa-solid fa-comments"></i> {{Lang.forum.dashboard}}</header>

    <div class="forum-adm-toolbar">
        <div class="forum-adm-toolbar-actions">
            <a class="button success" href="{{categoriesUrl}}"><i class="fa-solid fa-folder-tree"></i> {{Lang.forum.manage-categories}}</a>
            <a class="button" href="{{moderationUrl}}">
                <i class="fa-solid fa-shield-halved"></i> {{Lang.forum.open-moderation}}
                {% IF pendingCount > 0 %}<span class="forum-adm-badge-inline">{{pendingCount}}</span>{% ENDIF %}
            </a>
            <a class="button" href="{{maintenanceUrl}}">
                <i class="fa-solid fa-screwdriver-wrench"></i> Maintenance
                {% IF totalOrphansCount > 0 %}<span class="forum-adm-badge-inline">{{totalOrphansCount}}</span>{% ENDIF %}
            </a>
        </div>
        <div class="forum-adm-toolbar-actions">
            <a class="button secondary" href="{{publicUrl}}" target="_blank">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> {{Lang.forum.view-public}}
            </a>
        </div>
    </div>

    <div class="forum-adm-kpi-grid">
        <div class="forum-adm-kpi-card">
            <span class="forum-adm-kpi-label"><i class="fa-solid fa-folder"></i> {{Lang.forum.stats.categories}}</span>
            <span class="forum-adm-kpi-value">{{categoriesCount}}</span>
        </div>
        <div class="forum-adm-kpi-card">
            <span class="forum-adm-kpi-label"><i class="fa-solid fa-comments"></i> {{Lang.forum.stats.topics}}</span>
            <span class="forum-adm-kpi-value">{{topicsCount}}</span>
        </div>
        <div class="forum-adm-kpi-card">
            <span class="forum-adm-kpi-label"><i class="fa-solid fa-message"></i> {{Lang.forum.stats.posts}}</span>
            <span class="forum-adm-kpi-value">{{postsCount}}</span>
        </div>
        <div class="forum-adm-kpi-card {% IF pendingCount > 0 %}is-warning{% ELSE %}is-success{% ENDIF %}">
            <span class="forum-adm-kpi-label"><i class="fa-solid fa-clock"></i> {{Lang.forum.stats.pending}}</span>
            <span class="forum-adm-kpi-value">{{pendingCount}}</span>
        </div>
    </div>

    <div class="tabs-container" data-tab-key="forum-admin">
        <ul class="tabs-header">
            <li class="default-tab" data-tab="categories">
                <i class="fa-solid fa-folder-tree"></i> {{Lang.forum.categories}} ({{categoriesCount}})
            </li>
            <li data-tab="add-category">
                <i class="fa-solid fa-plus"></i> {{Lang.forum.category.add}}
            </li>
            <li data-tab="moderation">
                <i class="fa-solid fa-shield-halved"></i> {{Lang.forum.moderation}}
                {% IF pendingCount > 0 %}<span class="forum-adm-badge-inline">{{pendingCount}}</span>{% ENDIF %}
            </li>
            <li data-tab="maintenance">
                <i class="fa-solid fa-screwdriver-wrench"></i> Maintenance
                {% IF totalOrphansCount > 0 %}<span class="forum-adm-badge-inline">{{totalOrphansCount}}</span>{% ENDIF %}
            </li>
            <li data-tab="config">
                <i class="fa-solid fa-sliders"></i> Configuration
            </li>
        </ul>

        <ul class="tabs">
            <!-- TAB 1 : Arborescence des catégories -->
            <li class="tab">
                {% IF categories %}
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>{{Lang.forum.category.name}}</th>
                                <th>{{Lang.forum.category.slug}}</th>
                                <th>{{Lang.forum.stats.topics}}</th>
                                <th>{{Lang.forum.stats.posts}}</th>
                                <th>{{Lang.forum.actions}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% FOR category IN categories %}
                            <tr class="forum-adm-row-lvl1">
                                <td>
                                    <div class="forum-adm-cat-cell">
                                        {% IF category.icon %}
                                        <i class="{{category.icon}} forum-adm-cat-icon"></i>
                                        {% ELSE %}
                                        <i class="fa-solid fa-folder forum-adm-cat-icon"></i>
                                        {% ENDIF %}
                                        <strong>{{category.name}}</strong>
                                    </div>
                                </td>
                                <td><code>{{category.slug}}</code></td>
                                <td>{{category.topic_count}}</td>
                                <td>{{category.post_count}}</td>
                                <td>
                                    <div class="forum-adm-actions-cell">
                                        <a class="button small primary" href="{{category.editUrl}}"><i class="fa-solid fa-pen"></i> {{Lang.edit}}</a>
                                        <form method="POST" action="{{category.deleteUrl}}" class="forum-adm-inline-form" onsubmit="return confirm('{{Lang.forum.category.delete-confirm}}');">
                                            <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                            <button type="submit" class="button small alert"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            {% IF category.subcategories %}
                                {% FOR subcat IN category.subcategories %}
                                <tr class="forum-adm-row-lvl2">
                                    <td>
                                        <div class="forum-adm-cat-cell forum-adm-indent-lvl2">
                                            <span>↳</span>
                                            {% IF subcat.icon %}
                                            <i class="{{subcat.icon}} forum-adm-cat-icon-muted"></i>
                                            {% ENDIF %}
                                            <span>{{subcat.name}}</span>
                                        </div>
                                    </td>
                                    <td><code>{{subcat.slug}}</code></td>
                                    <td>{{subcat.topic_count}}</td>
                                    <td>{{subcat.post_count}}</td>
                                    <td>
                                        <div class="forum-adm-actions-cell">
                                            <a class="button small primary" href="{{subcat.editUrl}}"><i class="fa-solid fa-pen"></i> {{Lang.edit}}</a>
                                            <form method="POST" action="{{subcat.deleteUrl}}" class="forum-adm-inline-form" onsubmit="return confirm('{{Lang.forum.category.delete-confirm}}');">
                                                <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                                <button type="submit" class="button small alert"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                {% IF subcat.subcategories %}
                                    {% FOR forum IN subcat.subcategories %}
                                    <tr class="forum-adm-row-lvl3">
                                        <td>
                                            <div class="forum-adm-cat-cell forum-adm-indent-lvl3">
                                                <span>↳↳</span>
                                                {% IF forum.icon %}
                                                <i class="{{forum.icon}} forum-adm-cat-icon"></i>
                                                {% ENDIF %}
                                                <strong>{{forum.name}}</strong>
                                                <span class="evo-badge evo-badge-info">Niv. 3</span>
                                            </div>
                                        </td>
                                        <td><code>{{forum.slug}}</code></td>
                                        <td>{{forum.topic_count}}</td>
                                        <td>{{forum.post_count}}</td>
                                        <td>
                                            <div class="forum-adm-actions-cell">
                                                <a class="button small primary" href="{{forum.editUrl}}"><i class="fa-solid fa-pen"></i> {{Lang.edit}}</a>
                                                <form method="POST" action="{{forum.deleteUrl}}" class="forum-adm-inline-form" onsubmit="return confirm('{{Lang.forum.category.delete-confirm}}');">
                                                    <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                                    <button type="submit" class="button small alert"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    {% ENDFOR %}
                                {% ENDIF %}
                                {% ENDFOR %}
                            {% ENDIF %}
                            {% ENDFOR %}
                        </tbody>
                    </table>
                </div>
                {% ELSE %}
                <div class="evo-empty">
                    <p>{{Lang.forum.categories-empty}}</p>
                </div>
                {% ENDIF %}
            </li>

            <!-- TAB 2 : Création rapide de catégorie / salon -->
            <li class="tab">
                <form method="POST" action="{{addCategoryUrl}}">
                    <input type="hidden" name="_csrf" value="{{csrfToken}}">

                    <div class="forum-adm-form-grid">
                        <p>
                            <label for="quick_cat_name">{{Lang.forum.category.name}} *</label>
                            <input type="text" id="quick_cat_name" name="name" required placeholder="Ex : Discussions générales">
                        </p>
                        <p>
                            <label for="quick_cat_slug">{{Lang.forum.category.slug}}</label>
                            <input type="text" id="quick_cat_slug" name="slug" placeholder="discussions-generales">
                            <small>{{Lang.forum.category.slug-help}}</small>
                        </p>
                    </div>

                    <div class="forum-adm-form-grid">
                        <p>
                            <label for="quick_cat_parent">{{Lang.forum.category.parent}}</label>
                            <select id="quick_cat_parent" name="parent_id">
                                <option value="">Aucune (Catégorie principale - Niveau 1)</option>
                                {% FOR parentCat IN parentCategories %}
                                <option value="{{parentCat.id}}">{{parentCat.name}}</option>
                                {% ENDFOR %}
                            </select>
                        </p>
                        <p>
                            <label for="quick_cat_order">{{Lang.forum.category.order}}</label>
                            <input type="number" id="quick_cat_order" name="order_index" value="0">
                        </p>
                        <p>
                            <label for="quick_cat_icon">{{Lang.forum.category.icon}} (FontAwesome)</label>
                            <input type="text" id="quick_cat_icon" name="icon" placeholder="fa-solid fa-comments">
                        </p>
                    </div>

                    <p>
                        <label for="quick_cat_desc">{{Lang.forum.category.description}}</label>
                        <textarea id="quick_cat_desc" name="description" rows="3" placeholder=" Courte description affichée sous le titre..."></textarea>
                    </p>

                    <div class="forum-adm-form-actions">
                        <button type="submit" class="button primary">
                            <i class="fa-solid fa-save"></i> {{Lang.forum.category.save}}
                        </button>
                    </div>
                </form>
            </li>

            <!-- TAB 3 : Modération -->
            <li class="tab">
                {% IF pendingPosts %}
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>{{Lang.forum.topics}}</th>
                                <th>{{Lang.forum.author}}</th>
                                <th>{{Lang.forum.message}}</th>
                                <th>Date</th>
                                <th>{{Lang.forum.actions}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% FOR post IN pendingPosts %}
                            <tr>
                                <td>
                                    {% IF post.topic_title %}
                                        <a href="{{post.topic_url}}" target="_blank"><strong>{{post.topic_title}}</strong></a>
                                    {% ELSE %}
                                        #{{post.topic_id}}
                                    {% ENDIF %}
                                </td>
                                <td>
                                    <strong>{{post.author_name}}</strong>
                                    {% IF post.author_email %}<br><small>{{post.author_email}}</small>{% ENDIF %}
                                </td>
                                <td>
                                    <div class="forum-adm-desc-preview">{{post.content}}</div>
                                </td>
                                <td>{{post.created_at_formatted}}</td>
                                <td>
                                    <div class="forum-adm-actions-cell">
                                        <form method="POST" action="{{approveUrl}}" class="forum-adm-inline-form">
                                            <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                            <input type="hidden" name="post_id" value="{{post.id}}">
                                            <button type="submit" class="button small success"><i class="fa-solid fa-check"></i> {{Lang.forum.approve}}</button>
                                        </form>
                                        <form method="POST" action="{{deleteModerationUrl}}" class="forum-adm-inline-form" onsubmit="return confirm('{{Lang.forum.delete-confirm}}');">
                                            <input type="hidden" name="_csrf" value="{{csrfToken}}">
                                            <input type="hidden" name="post_id" value="{{post.id}}">
                                            <button type="submit" class="button small alert"><i class="fa-solid fa-trash"></i> {{Lang.forum.delete}}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            {% ENDFOR %}
                        </tbody>
                    </table>
                </div>
                {% ELSE %}
                <div class="evo-empty">
                    <i class="fa-solid fa-circle-check"></i> Aucun message en attente de modération.
                </div>
                {% ENDIF %}
            </li>

            <!-- TAB 4 : Maintenance -->
            <li class="tab">
                <div class="forum-adm-kpi-grid">
                    <div class="forum-adm-kpi-card {% IF orphanSubcatsCount > 0 %}is-warning{% ELSE %}is-success{% ENDIF %}">
                        <span class="forum-adm-kpi-label">Sous-catégories orphelines</span>
                        <span class="forum-adm-kpi-value">{{orphanSubcatsCount}}</span>
                    </div>
                    <div class="forum-adm-kpi-card {% IF orphanTopicsCount > 0 %}is-warning{% ELSE %}is-success{% ENDIF %}">
                        <span class="forum-adm-kpi-label">Sujets orphelins</span>
                        <span class="forum-adm-kpi-value">{{orphanTopicsCount}}</span>
                    </div>
                    <div class="forum-adm-kpi-card {% IF orphanPostsCount > 0 %}is-warning{% ELSE %}is-success{% ENDIF %}">
                        <span class="forum-adm-kpi-label">Messages orphelins</span>
                        <span class="forum-adm-kpi-value">{{orphanPostsCount}}</span>
                    </div>
                </div>

                {% IF totalOrphansCount > 0 %}
                <div class="forum-adm-center-actions">
                    <form action="{{maintenanceRunUrl}}" method="POST">
                        <input type="hidden" name="_csrf" value="{{csrfToken}}">
                        <button type="submit" class="button alert">
                            <i class="fa-solid fa-recycle"></i> Récupérer les {{totalOrphansCount}} élément(s) orphelin(s)
                        </button>
                    </form>
                    <p><small>Les éléments orphelins seront regroupés dans une catégorie « Corbeille / Récupération ».</small></p>
                </div>
                {% ELSE %}
                <div class="evo-notice evo-notice-success">
                    <i class="fa-solid fa-check-circle"></i> Intégrité vérifiée : aucun élément orphelin détecté dans la base du forum.
                </div>
                {% ENDIF %}
            </li>

            <!-- TAB 5 : Configuration -->
            <li class="tab">
                <form method="POST" action="{{configSaveUrl}}">
                    <input type="hidden" name="_csrf" value="{{csrfToken}}">

                    <div class="forum-adm-section-box">
                        <h3><i class="fa-solid fa-shield-halved"></i> Modération & Fonctionnalités</h3>
                        <p>
                            <label>
                                <input type="checkbox" name="moderate_posts" value="1" {% IF moderatePosts %}checked{% ENDIF %}>
                                Modérer les messages avant publication
                            </label>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="allow_anonymous" value="1" {% IF allowAnonymous %}checked{% ENDIF %}>
                                Autoriser les utilisateurs non connectés à créer des sujets
                            </label>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="enable_likes" value="1" {% IF enableLikes %}checked{% ENDIF %}>
                                Activer le système de « J'aime » sur les messages
                            </label>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="enable_notifications" value="1" {% IF enableNotifications %}checked{% ENDIF %}>
                                Activer les notifications lors d'une réponse à un sujet
                            </label>
                        </p>
                    </div>

                    <div class="forum-adm-section-box">
                        <h3><i class="fa-solid fa-tags"></i> Terminologie & Pagination</h3>
                        <div class="forum-adm-form-grid">
                            <p>
                                <label for="cfg_lvl1">Terme de Niveau 1 (Racine)</label>
                                <input type="text" id="cfg_lvl1" name="level1_label" value="{{level1Label}}" placeholder="Catégorie">
                            </p>
                            <p>
                                <label for="cfg_lvl2">Terme de Niveau 2 (Sous-groupe)</label>
                                <input type="text" id="cfg_lvl2" name="level2_label" value="{{level2Label}}" placeholder="Sous-catégorie">
                            </p>
                            <p>
                                <label for="cfg_lvl3">Terme de Niveau 3 (Discussion)</label>
                                <input type="text" id="cfg_lvl3" name="level3_label" value="{{level3Label}}" placeholder="Salon de discussion">
                            </p>
                        </div>

                        <div class="forum-adm-form-grid">
                            <p>
                                <label for="cfg_topics_page">Sujets par page</label>
                                <input type="number" id="cfg_topics_page" name="topics_per_page" value="{{topicsPerPage}}" min="5" max="100">
                            </p>
                            <p>
                                <label for="cfg_posts_page">Messages par page</label>
                                <input type="number" id="cfg_posts_page" name="posts_per_page" value="{{postsPerPage}}" min="5" max="100">
                            </p>
                        </div>
                    </div>

                    <div class="forum-adm-form-actions">
                        <button type="submit" class="button primary">
                            <i class="fa-solid fa-save"></i> Enregistrer la configuration
                        </button>
                    </div>
                </form>
            </li>
        </ul>
    </div>
</section>
