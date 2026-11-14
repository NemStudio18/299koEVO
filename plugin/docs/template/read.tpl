<div class="docs-kb-container">
    <!-- Colonne gauche : Navigation arborescente / Sommaire -->
    <aside class="docs-kb-sidebar">
        <div class="docs-kb-sidebar-header">
            <a href="{{ runPlugin.getPublicUrl }}" class="docs-kb-home-link">
                <i class="fa-solid fa-book-open"></i> <strong>Documentation</strong>
            </a>
            <div class="docs-search-box">
                <input type="text" id="docsSearchFilter" placeholder="Filtrer les guides..." oninput="filterDocsPages(this.value)">
            </div>
        </div>

        <nav class="docs-kb-tree" id="docsKbTree">
            {% if allCategories is not empty %}
                {% for cat in allCategories %}
                    <div class="docs-cat-group">
                        <div class="docs-cat-title">
                            <i class="fa-solid fa-folder-open"></i> {{ cat.label }}
                        </div>
                        <ul class="docs-cat-pages">
                            {% for page in allPages %}
                                {% if page.getId() in cat.items %}
                                    <li class="docs-page-item {% if page.getId() == item.getId() %}active{% endif %}" data-title="{{ page.getName() }}">
                                        <a href="/docs/article/{{ page.getName()|strToUrl }}-{{ page.getId() }}">
                                            <i class="fa-regular fa-file-lines"></i> {{ page.getName() }}
                                        </a>
                                    </li>
                                {% endif %}
                            {% endfor %}
                        </ul>
                    </div>
                {% endfor %}
            {% else %}
                <ul class="docs-cat-pages">
                    {% for page in allPages %}
                        <li class="docs-page-item {% if page.getId() == item.getId() %}active{% endif %}" data-title="{{ page.getName() }}">
                            <a href="/docs/article/{{ page.getName()|strToUrl }}-{{ page.getId() }}">
                                <i class="fa-regular fa-file-lines"></i> {{ page.getName() }}
                            </a>
                        </li>
                    {% endfor %}
                </ul>
            {% endif %}
        </nav>
    </aside>

    <!-- Colonne droite : Contenu de la documentation -->
    <main class="docs-kb-content">
        <div class="docs-kb-breadcrumbs">
            <a href="{{ runPlugin.getPublicUrl }}"><i class="fa-solid fa-house"></i> Base de connaissances</a>
            {% for cat in categories %}
                <span>/</span> <a href="{{ cat.url }}">{{ cat.label }}</a>
            {% endfor %}
            <span>/</span> <span class="current">{{ item.getName() }}</span>
        </div>

        <article class="docs-article">
            <header class="docs-article-header">
                <h1>{{ item.getName() }}</h1>
                <div class="docs-article-meta">
                    <span class="meta-date"><i class="fa-regular fa-calendar"></i> {{ item.getReadableDate() }}</span>
                    {% if categories %}
                        <span class="meta-cat">
                            <i class="fa-solid fa-tags"></i>
                            {% for cat in categories %}
                                <a href="{{ cat.url }}" class="badge">{{ cat.label }}</a>
                            {% endfor %}
                        </span>
                    {% endif %}
                </div>
            </header>

            {% if item.getImg() %}
                <div class="docs-featured-img">
                    <img src="{{ item.getImgUrl() }}" alt="{{ item.getName() }}">
                </div>
            {% endif %}

            {% if TOC %}
                <div class="docs-toc-card">
                    <div class="docs-toc-title"><i class="fa-solid fa-list-ol"></i> Sommaire</div>
                    {{ TOC|raw }}
                </div>
            {% endif %}

            <div class="docs-body markdown-body">
                {{ generatedHtml|raw }}
            </div>

            <!-- Liens de navigation Précédent / Suivant -->
            <footer class="docs-kb-footer-nav">
                <div class="docs-nav-prev">
                    {% if prevPage %}
                        <a href="{{ prevPage.url }}" class="docs-nav-card prev">
                            <span class="nav-direction"><i class="fa-solid fa-arrow-left"></i> Précédent</span>
                            <span class="nav-title">{{ prevPage.name }}</span>
                        </a>
                    {% endif %}
                </div>
                <div class="docs-nav-next">
                    {% if nextPage %}
                        <a href="{{ nextPage.url }}" class="docs-nav-card next">
                            <span class="nav-direction">Suivant <i class="fa-solid fa-arrow-right"></i></span>
                            <span class="nav-title">{{ nextPage.name }}</span>
                        </a>
                    {% endif %}
                </div>
            </footer>
        </article>
    </main>
</div>

<style>
.docs-kb-container { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; max-width: 1280px; margin: 1.5rem auto; padding: 0 1rem; }
@media (max-width: 860px) { .docs-kb-container { grid-template-columns: 1fr; } }
.docs-kb-sidebar { background: var(--evo-surface, #f8fafc); border: 1px solid var(--evo-border, #e2e8f0); border-radius: 12px; padding: 1.25rem; height: fit-content; position: sticky; top: 80px; }
.docs-kb-sidebar-header { margin-bottom: 1.25rem; border-bottom: 1px solid var(--evo-border, #e2e8f0); padding-bottom: 0.85rem; }
.docs-kb-home-link { font-size: 1.1rem; color: var(--evo-text, inherit); text-decoration: none; display: flex; align-items: center; gap: 8px; margin-bottom: 0.75rem; }
.docs-search-box input { width: 100%; box-sizing: border-box; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--evo-border, #cbd5e1); font-size: 0.85rem; background: var(--evo-surface-alt, #fff); color: var(--evo-text, inherit); }
.docs-cat-group { margin-bottom: 1rem; }
.docs-cat-title { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: var(--evo-text-muted, #64748b); margin-bottom: 0.4rem; display: flex; align-items: center; gap: 6px; }
.docs-cat-pages { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 3px; }
.docs-page-item a { display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 6px; text-decoration: none; color: var(--evo-text, #334155); font-size: 0.88rem; transition: all 0.15s ease; }
.docs-page-item a:hover { background: rgba(2, 132, 199, 0.08); color: var(--evo-primary, #0284c7); }
.docs-page-item.active a { background: var(--evo-primary, #0284c7); color: #ffffff; font-weight: 600; }
.docs-kb-content { min-width: 0; }
.docs-kb-breadcrumbs { font-size: 0.85rem; color: var(--evo-text-muted, #64748b); margin-bottom: 1rem; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.docs-kb-breadcrumbs a { color: var(--evo-text-muted, #64748b); text-decoration: none; }
.docs-kb-breadcrumbs a:hover { color: var(--evo-primary, #0284c7); }
.docs-kb-breadcrumbs .current { color: var(--evo-text, inherit); font-weight: 600; }
.docs-article { background: var(--evo-surface, #ffffff); border: 1px solid var(--evo-border, #e2e8f0); border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.docs-article-header { border-bottom: 1px solid var(--evo-border, #e2e8f0); padding-bottom: 1.25rem; margin-bottom: 1.5rem; }
.docs-article-header h1 { font-size: 2.2rem; margin: 0 0 0.5rem; line-height: 1.25; }
.docs-article-meta { font-size: 0.85rem; color: var(--evo-text-muted, #64748b); display: flex; gap: 1rem; align-items: center; }
.docs-featured-img img { max-width: 100%; border-radius: 8px; margin-bottom: 1.5rem; }
.docs-toc-card { background: var(--evo-surface-alt, #f8fafc); border: 1px solid var(--evo-border, #e2e8f0); border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.75rem; }
.docs-toc-title { font-weight: 700; margin-bottom: 0.5rem; font-size: 0.95rem; }
.docs-body { font-size: 1rem; line-height: 1.7; color: var(--evo-text, inherit); margin-bottom: 2.5rem; }
.docs-body h2 { font-size: 1.5rem; margin: 2rem 0 1rem; border-bottom: 1px solid var(--evo-border, #e2e8f0); padding-bottom: 0.4rem; }
.docs-body h3 { font-size: 1.25rem; margin: 1.5rem 0 0.75rem; }
.docs-body pre { background: var(--evo-surface-dark, #0f172a); color: #f8fafc; padding: 1rem; border-radius: 8px; overflow-x: auto; }
.docs-kb-footer-nav { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; border-top: 1px solid var(--evo-border, #e2e8f0); padding-top: 1.5rem; margin-top: 2rem; }
.docs-nav-card { display: flex; flex-direction: column; padding: 1rem; border: 1px solid var(--evo-border, #e2e8f0); border-radius: 8px; text-decoration: none; color: var(--evo-text, inherit); transition: all 0.18s ease; }
.docs-nav-card:hover { border-color: var(--evo-primary, #0284c7); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.06); }
.docs-nav-card.next { text-align: right; }
.nav-direction { font-size: 0.8rem; color: var(--evo-text-muted, #64748b); font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
.nav-title { font-weight: 700; font-size: 0.95rem; color: var(--evo-primary, #0284c7); }
</style>

<script>
function filterDocsPages(q) {
    q = (q || '').toLowerCase().trim();
    const items = document.querySelectorAll('#docsKbTree .docs-page-item');
    items.forEach(el => {
        const title = (el.getAttribute('data-title') || '').toLowerCase();
        el.style.display = (!q || title.includes(q)) ? '' : 'none';
    });
}
</script>
