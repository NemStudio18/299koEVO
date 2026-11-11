<section class="module-card">
    <header><i class="fa-solid fa-magnifying-glass-chart"></i> {{ Lang.seo.name }}</header>

    <form method="post" action="{{ ROUTER.generate("seo-admin-save")}}" enctype="multipart/form-data">
        {{ SHOW.tokenField }}

        <div class="tabs-container" data-tab-key="seo">
            <ul class="tabs-header">
                <li class="default-tab" data-tab="general">
                    <i class="fa-solid fa-sliders"></i> {{ Lang.seo.tab.general }}
                </li>
                <li data-tab="social">
                    <i class="fa-solid fa-share-nodes"></i> {{ Lang.seo.tab.social }}
                </li>
                <li data-tab="redirects">
                    <i class="fa-solid fa-route"></i> {{ Lang.seo.tab.redirects }}
                </li>
                <li data-tab="help">
                    <i class="fa-solid fa-circle-question"></i> {{ Lang.seo.tab.help }}
                </li>
            </ul>

            <ul class="tabs">
                <!-- TAB GENERAL -->
                <li class="tab">
                    <section>
                        <header>{{ Lang.seo.display }}</header>
                        <p>
                            <label for="position">{{ Lang.seo.menu-position }}</label>
                            <select name="position" id="position">
                                <option value="menu" {% if position == "menu" %}selected{% endif %}>{{ Lang.seo.nav-menu }}</option>
                                <option value="footer" {% if position == "footer" %}selected{% endif %}>{{ Lang.seo.top-footer-page }}</option>
                                <option value="endfooter" {% if position == "endfooter" %}selected{% endif %}>{{ Lang.seo.bottom-footer-page }}</option>
                                <option value="float" {% if position == "float" %}selected{% endif %}>{{ Lang.seo.float }}</option>
                            </select>
                        </p>
                    </section>
                    <section>
                        <header>{{ Lang.seo.google }}</header>
                        <p>
                            <label for="trackingId">{{ Lang.seo.analytics.id }}</label>
                            <input type="text" name="trackingId" id="trackingId" value="{{ runPlugin.getConfigVal("trackingId") }}" />
                        </p>
                        <p>
                            <label for="wt">{{ Lang.seo.analytics.meta }}</label>
                            <input type="text" name="wt" id="wt" value="{{ runPlugin.getConfigVal("wt") }}" />
                        </p>
                    </section>
                    <section>
                        <header>{{ Lang.seo.sitemap.title }}</header>
                        <p>{{ Lang.seo.sitemap.help }}</p>
                        <button type="submit" formaction="{{ ROUTER.generate("seo-admin-generate-sitemap") }}" class="button secondary">
                            <i class="fa-solid fa-sitemap"></i> {{ Lang.seo.sitemap.generate }}
                        </button>
                    </section>
                </li>

                <!-- TAB SOCIAL -->
                <li class="tab">
                    <section>
                        <header>{{ Lang.seo.og.image.global }}</header>
                        <p>
                            <label for="defaultOgImage">{{ Lang.seo.og.image.label }}</label>
                            <input type="text" name="defaultOgImage" id="defaultOgImage" value="{{ runPlugin.getConfigVal("defaultOgImage") }}" placeholder="https://..." />
                            <small>{{ Lang.seo.og.image.help }}</small>
                        </p>
                    </section>

                    <section>
                        <header>{{ Lang.seo.socials-links }}</header>
                        {% set social = seoGetSocialVars() %}
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                        {% for k, v in social %}
                        <p>
                            <label for="{{ v }}"><i class="fa-brands fa-{{ v }}"></i>&nbsp;{{ k }}</label>
                            <input placeholder="" type="text" name="{{ v }}" id="{{ v }}" value="{{ runPlugin.getConfigVal(v) }}" />
                        </p>
                        {% endfor %}
                        </div>
                    </section>

                    <section>
                        <header>{{ Lang.seo.share-settings }}</header>
                        <div class="seo-network" style="margin-bottom: 14px;">
                            <label><input type="checkbox" name="facebook_enabled" {% if socialConfig.facebook.enabled %}checked{% endif %}> Facebook</label>
                            <div class="seo-network-fields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px;">
                                <input type="text" name="facebook_appId" value="{{ socialConfig.facebook.appId }}" placeholder="App ID">
                                <input type="text" name="facebook_accessToken" value="{{ socialConfig.facebook.accessToken }}" placeholder="Access Token">
                            </div>
                        </div>
                        <div class="seo-network">
                            <label><input type="checkbox" name="x_enabled" {% if socialConfig.x.enabled %}checked{% endif %}> X (Twitter)</label>
                            <input type="text" name="x_bearerToken" value="{{ socialConfig.x.bearerToken }}" placeholder="Bearer Token" style="margin-top: 8px;">
                        </div>
                    </section>
                </li>

                <!-- TAB REDIRECTS -->
                <li class="tab">
                    <section>
                        <header>{{ Lang.seo.redirects.new }}</header>
                        <div style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                            <p style="flex: 2; min-width: 200px;">
                                <label>{{ Lang.seo.redirects.from }}</label>
                                <input type="text" name="new_redirect_from" placeholder="/ancienne-page">
                            </p>
                            <p style="flex: 2; min-width: 200px;">
                                <label>{{ Lang.seo.redirects.to }}</label>
                                <input type="text" name="new_redirect_to" placeholder="/nouvelle-page">
                            </p>
                            <p style="flex: 1; min-width: 140px;">
                                <label>{{ Lang.seo.redirects.code }}</label>
                                <select name="new_redirect_code">
                                    <option value="301">301 (Permanent)</option>
                                    <option value="302">302 (Temporaire)</option>
                                </select>
                            </p>
                        </div>
                    </section>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ Lang.seo.redirects.from }}</th>
                                    <th>{{ Lang.seo.redirects.to }}</th>
                                    <th>{{ Lang.seo.redirects.code }}</th>
                                    <th>{{ Lang.seo.redirects.hits }}</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {% for r in redirects %}
                                <tr>
                                    <td><code>{{ r.old_url }}</code></td>
                                    <td><code>{{ r.new_url }}</code></td>
                                    <td>{{ r.code }}</td>
                                    <td>{{ r.hits }} {% if r.last_hit %}<small>({{ r.last_hit }})</small>{% endif %}</td>
                                    <td style="text-align: right;">
                                        <button type="submit" formaction="{{ ROUTER.generate("seo-admin-delete-redirect", {"id": r.id}) }}" class="button alert small" onclick="return confirm('Supprimer cette redirection ?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                {% endfor %}
                            </tbody>
                        </table>
                    </div>
                </li>

                <!-- TAB HELP -->
                <li class="tab">
                    <section>
                        <header><i class="fa-brands fa-google"></i> {{ Lang.seo.google }}</header>
                        <p><strong>{{ Lang.seo.analytics.id }} :</strong> {{ Lang.seo.analytics.id.desc }}<br>
                        <small>Note technique : ce champ injecte le script historique Google Analytics (<code>analytics.js</code>). Si vous préférez une mesure locale sans traceur Google, laissez ce champ vide et utilisez <strong>LightStats</strong>.</small></p>
                        <p><strong>{{ Lang.seo.analytics.meta }} :</strong> {{ Lang.seo.analytics.meta.desc }} Permet à Google Search Console de vérifier que vous êtes propriétaire du site.</p>
                        <p><strong>{{ Lang.seo.sitemap.title }} :</strong> Génère le fichier <code>/sitemap.xml</code> à la racine du site afin d'aider les moteurs de recherche à indexer vos pages et articles publics.</p>
                    </section>

                    <section>
                        <header><i class="fa-solid fa-share-nodes"></i> OpenGraph, Liens &amp; Partage Social (Facebook / X)</header>
                        <p><strong>Image OpenGraph globale :</strong> Définit l'image affichée par défaut en aperçu lorsqu'une page du site est partagée sur Facebook, WhatsApp, Discord ou LinkedIn (si la page ou l'article n'a pas déjà d'image mise en avant).</p>
                        <p><strong>Liens sur les réseaux sociaux :</strong> Affiche les icônes vers vos profils officiels dans la zone choisie (<em>Menu</em>, <em>Pied de page</em> ou <em>Flottant</em>).</p>
                        <p><strong>Partage automatique (App ID Facebook / Access Token / Bearer Token X) :</strong> Lorsqu'un réseau est coché et configuré, le plugin tente de publier automatiquement un lien sur votre Page Facebook ou compte X à chaque enregistrement d'un nouvel article de blog (via l'API Graph Facebook avec votre <em>Page Access Token</em> issu de votre App Facebook, ou l'API X avec votre <em>Bearer Token</em>).</p>
                    </section>

                    <section>
                        <header><i class="fa-solid fa-route"></i> Fonctionnement des Redirections (301 / 302)</header>
                        <p>Avant d'afficher une page ou une erreur 404, le site vérifie si l'URL demandée correspond à une règle enregistrée :</p>
                        <ul>
                            <li><strong>301 (Permanent) :</strong> à utiliser quand une ancienne page a changé d'adresse définitivement (transfère le référencement vers la nouvelle URL).</li>
                            <li><strong>302 (Temporaire) :</strong> à utiliser pour une redirection ponctuelle sans modifier l'indexation définitive.</li>
                        </ul>
                        <p>La colonne <strong>{{ Lang.seo.redirects.hits }}</strong> indique combien de fois chaque redirection a été déclenchée côté visiteurs.</p>
                    </section>
                </li>
            </ul>
        </div>

        <div style="margin-top: 16px;">
            <button type="submit" class="button success">
                <i class="fa-solid fa-check"></i> {{ Lang.submit }}
            </button>
        </div>
    </form>
</section>
