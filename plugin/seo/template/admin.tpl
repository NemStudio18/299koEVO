<section class="module-card">
    <header><i class="fa-solid fa-magnifying-glass-chart"></i> {{ Lang.seo.name|default("SEO & Référencement") }}</header>

    <form method="post" action="{{ ROUTER.generate("seo-admin-save")}}" enctype="multipart/form-data">
        {{ SHOW.tokenField }}

        <div class="tabs-container" data-tab-key="seo">
            <ul class="tabs-header">
                <li class="default-tab" data-tab="general">
                    <i class="fa-solid fa-sliders"></i> {{ Lang.seo.tab.general|default("Général") }}
                </li>
                <li data-tab="social">
                    <i class="fa-solid fa-share-nodes"></i> {{ Lang.seo.tab.social|default("Social / OG") }}
                </li>
                <li data-tab="redirects">
                    <i class="fa-solid fa-route"></i> {{ Lang.seo.tab.redirects|default("Redirections") }}
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
                        <header>{{ Lang.seo.og.image.global|default("Image OpenGraph Globale") }}</header>
                        <p>
                            <label for="defaultOgImage">{{ Lang.seo.og.image.label|default("URL ou fichier Image") }}</label>
                            <input type="text" name="defaultOgImage" id="defaultOgImage" value="{{ runPlugin.getConfigVal("defaultOgImage") }}" placeholder="https://..." />
                            <small>{{ Lang.seo.og.image.help|default("Cette image sera utilisée si aucune autre image n'est définie pour la page.") }}</small>
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
                        <header>{{ Lang.seo.redirects.new|default("Ajouter une redirection") }}</header>
                        <div style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                            <p style="flex: 2; min-width: 200px;">
                                <label>{{ Lang.seo.redirects.from|default("URL Source (ex: /ancienne-page)") }}</label>
                                <input type="text" name="new_redirect_from" placeholder="/ancienne-page">
                            </p>
                            <p style="flex: 2; min-width: 200px;">
                                <label>{{ Lang.seo.redirects.to|default("URL Destination (ex: /nouvelle-page)") }}</label>
                                <input type="text" name="new_redirect_to" placeholder="/nouvelle-page">
                            </p>
                            <p style="flex: 1; min-width: 140px;">
                                <label>{{ Lang.seo.redirects.code|default("Code") }}</label>
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
                                    <th>{{ Lang.seo.redirects.from|default("Source") }}</th>
                                    <th>{{ Lang.seo.redirects.to|default("Destination") }}</th>
                                    <th>{{ Lang.seo.redirects.code|default("Code") }}</th>
                                    <th>{{ Lang.seo.redirects.hits|default("Hits") }}</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {% for r in redirects %}
                                <tr>
                                    <td><code>{{ r.old_url }}</code></td>
                                    <td><code>{{ r.new_url }}</code></td>
                                    <td>{{ r.code }}</td>
                                    <td>{{ r.hits }} <small>({{ r.last_hit|default("-") }})</small></td>
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
            </ul>
        </div>

        <div style="margin-top: 16px;">
            <button type="submit" class="button success">
                <i class="fa-solid fa-check"></i> {{ Lang.submit }}
            </button>
        </div>
    </form>
</section>
