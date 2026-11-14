<section class="module-card">
    <header><i class="fa-solid fa-rss"></i> Configuration des Flux RSS Universels</header>

    <div class="tabs-container" data-tab-key="rss">
        <ul class="tabs-header">
            <li class="default-tab" data-tab="config">
                <i class="fa-solid fa-sliders"></i> Réglages &amp; Sources détectées ({{ providersCount }})
            </li>
            <li data-tab="urls">
                <i class="fa-solid fa-link"></i> Liens des flux disponibles
            </li>
        </ul>

        <ul class="tabs">
            <!-- Tab 1: Réglages -->
            <li class="tab">
                <form action="{{ saveUrl }}" method="POST">
                    <input type="hidden" name="_csrf" value="{{ csrfToken }}">

                    <div class="field">
                        <label>
                            <input type="checkbox" name="activate" {% if activateChecked %}checked{% endif %}>
                            <strong>Activer la génération des flux RSS</strong> (balise auto-discovery injectée dans le <code>&lt;head&gt;</code>)
                        </label>
                    </div>

                    <div class="field">
                        <label for="feed_title">Titre principal du flux global</label>
                        <input type="text" id="feed_title" name="feed_title" value="{{ feedTitle }}" required>
                    </div>

                    <div class="field">
                        <label for="feed_description">Description du flux</label>
                        <input type="text" id="feed_description" name="feed_description" value="{{ feedDesc }}">
                    </div>

                    <div class="field">
                        <label for="max_items">Nombre maximum d'articles / items diffusés (5 à 100)</label>
                        <input type="number" id="max_items" name="max_items" value="{{ maxItems }}" min="5" max="100">
                    </div>

                    <fieldset style="border: 1px solid var(--evo-border, #cbd5e1); border-radius: 8px; padding: 1rem 1.25rem; margin: 1.5rem 0;">
                        <legend style="font-weight: 700; padding: 0 0.5rem;"><i class="fa-solid fa-wand-magic-sparkles"></i> Plugins &amp; Sources détectés automatiquement ({{ providersCount }})</legend>
                        <p style="color: var(--evo-text-muted, #64748b); font-size: 0.88rem; margin: 0 0 1rem;">
                            Le moteur scanne dynamiquement tous vos plugins installés et actifs. Cochez les sections à inclure dans votre flux universel :
                        </p>

                        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                            {% for slug, prov in providers %}
                                <label style="display: flex; align-items: flex-start; gap: 10px; font-weight: 500; cursor: pointer; padding: 6px; border-radius: 6px; transition: background 0.15s ease;">
                                    <input type="checkbox" name="enable_{{ slug }}" {% if prov.enabled %}checked{% endif %} style="margin-top: 3px;">
                                    <div>
                                        <span><i class="fa-solid {{ prov.icon }}"></i> <strong>{{ prov.name }}</strong></span>
                                        <div style="font-size: 0.8rem; color: var(--evo-text-muted, #64748b);">{{ prov.description }}</div>
                                    </div>
                                </label>
                            {% endfor %}
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <button type="submit" class="button primary"><i class="fa-solid fa-save"></i> Enregistrer les flux RSS</button>
                    </div>
                </form>
            </li>

            <!-- Tab 2: Liens des flux -->
            <li class="tab">
                <section>
                    <header><i class="fa-solid fa-rss"></i> Flux Global du site</header>
                    <p>Aggrège toutes les sources activées ci-contre par ordre chronologique :</p>
                    <p><code><a href="/rss.xml" target="_blank">/rss.xml</a></code> ou <code><a href="/feed" target="_blank">/feed</a></code></p>
                </section>

                <section>
                    <header><i class="fa-solid fa-filter"></i> Flux Spécifiques par Plugin (détectés automatiquement)</header>
                    <p>Vos visiteurs ou lecteurs d'actualités peuvent également s'abonner individuellement à un plugin précis :</p>
                    <ul>
                        {% for slug, prov in providers %}
                            <li>
                                <strong><i class="fa-solid {{ prov.icon }}"></i> {{ prov.name }} :</strong> 
                                <code><a href="/rss/{{ slug }}" target="_blank">/rss/{{ slug }}</a></code>
                            </li>
                        {% endfor %}
                    </ul>
                </section>
            </li>
        </ul>
    </div>
</section>

<style>
.field { margin-bottom: 1rem; }
.field label { display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; }
.field input[type="text"], .field input[type="number"] { width: 100%; max-width: 500px; }
.form-actions { margin-top: 1.5rem; }
</style>
