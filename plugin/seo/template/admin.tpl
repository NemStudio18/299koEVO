<style>
    #seo-tabs { display: flex; gap: 1rem; border-bottom: 2px solid #eee; margin-bottom: 1.5rem; }
    .seo-tab { padding: 0.75rem 1.5rem; cursor: pointer; border: 1px solid transparent; border-bottom: none; border-radius: 8px 8px 0 0; font-weight: 500; color: #666; }
    .seo-tab.active { background: #fff; border-color: #eee; color: #2563eb; border-bottom: 2px solid #2563eb; margin-bottom: -2px; }
    .seo-tab-content { display: none; }
    .seo-tab-content.active { display: block; }
    .redirect-table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .redirect-table th, .redirect-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #f3f4f6; }
    .redirect-table th { background: #f9fafb; font-weight: 600; color: #374151; }
    .btn-delete { color: #dc2626; cursor: pointer; border: none; background: none; }
    .btn-delete:hover { color: #991b1b; }
</style>

<div id="seo-tabs">
    <div class="seo-tab active" onclick="showTab('tab-general')">{{ Lang.seo.tab.general|default("Général") }}</div>
    <div class="seo-tab" onclick="showTab('tab-social')">{{ Lang.seo.tab.social|default("Social / OG") }}</div>
    <div class="seo-tab" onclick="showTab('tab-redirects')">{{ Lang.seo.tab.redirects|default("Redirections") }}</div>
</div>

<form method="post" action="{{ ROUTER.generate("seo-admin-save")}}" enctype="multipart/form-data">
    {{ SHOW.tokenField }}

    <!-- TAB GENERAL -->
    <div id="tab-general" class="seo-tab-content active">
        <section>
            <header>{{ Lang.seo.display }}</header>
            <p>
                <label for="position">{{ Lang.seo.menu-position }}</label><br>
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
                <label for="trackingId">{{ Lang.seo.analytics.id }}</label><br>
                <input type="text" name="trackingId" id="trackingId" value="{{ runPlugin.getConfigVal("trackingId") }}" />
            </p>
            <p>
                <label for="wt">{{ Lang.seo.analytics.meta }}</label><br>
                <input type="text" name="wt" id="wt" value="{{ runPlugin.getConfigVal("wt") }}" />
            </p>
        </section>
        <section>
            <header>{{ Lang.seo.sitemap.title }}</header>
            <p>{{ Lang.seo.sitemap.help }}</p>
            <button type="submit" formaction="{{ ROUTER.generate("seo-admin-generate-sitemap") }}" class="button">{{ Lang.seo.sitemap.generate }}</button>
        </section>
    </div>

    <!-- TAB SOCIAL -->
    <div id="tab-social" class="seo-tab-content">
        <section>
            <header>{{ Lang.seo.og.image.global|default("Image OpenGraph Globale") }}</header>
            <p>
                <label for="defaultOgImage">{{ Lang.seo.og.image.label|default("URL ou fichier Image") }}</label><br>
                <input type="text" name="defaultOgImage" id="defaultOgImage" value="{{ runPlugin.getConfigVal("defaultOgImage") }}" placeholder="https://..." /><br>
                <small>{{ Lang.seo.og.image.help|default("Cette image sera utilisée si aucune autre image n'est définie pour la page.") }}</small>
            </p>
        </section>

        <section>
            <header>{{ Lang.seo.socials-links }}</header>
            {% set social = seoGetSocialVars() %}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            {% for k, v in social %}
            <p>
                <label for="{{ v }}"><i class="fa-brands fa-{{ v }}"></i>&nbsp;{{ k }}</label><br>
                <input placeholder="" type="text" name="{{ v }}" id="{{ v }}" value="{{ runPlugin.getConfigVal(v) }}" />
            </p>
            {% endfor %}
            </div>
        </section>

        <section>
            <header>{{ Lang.seo.share-settings }}</header>
            <div class="seo-network">
                <label><input type="checkbox" name="facebook_enabled" {% if socialConfig.facebook.enabled %}checked{% endif %}> Facebook</label>
                <div class="seo-network-fields">
                    <input type="text" name="facebook_appId" value="{{ socialConfig.facebook.appId }}" placeholder="App ID">
                    <input type="text" name="facebook_accessToken" value="{{ socialConfig.facebook.accessToken }}" placeholder="Access Token">
                </div>
            </div>
            <div class="seo-network">
                <label><input type="checkbox" name="x_enabled" {% if socialConfig.x.enabled %}checked{% endif %}> X (Twitter)</label>
                <input type="text" name="x_bearerToken" value="{{ socialConfig.x.bearerToken }}" placeholder="Bearer Token">
            </div>
        </section>
    </div>

    <!-- TAB REDIRECTS -->
    <div id="tab-redirects" class="seo-tab-content">
        <section>
            <header>{{ Lang.seo.redirects.new|default("Ajouter une redirection") }}</header>
            <div style="display: flex; gap: 1rem; align-items: flex-end;">
                <p style="flex: 2;">
                    <label>{{ Lang.seo.redirects.from|default("URL Source (ex: /ancienne-page)") }}</label><br>
                    <input type="text" name="new_redirect_from" placeholder="/ancienne-page">
                </p>
                <p style="flex: 2;">
                    <label>{{ Lang.seo.redirects.to|default("URL Destination (ex: /nouvelle-page)") }}</label><br>
                    <input type="text" name="new_redirect_to" placeholder="/nouvelle-page">
                </p>
                <p style="flex: 1;">
                    <label>{{ Lang.seo.redirects.code|default("Code") }}</label><br>
                    <select name="new_redirect_code">
                        <option value="301">301 (Permanent)</option>
                        <option value="302">302 (Temporaire)</option>
                    </select>
                </p>
            </div>
        </section>

        <table class="redirect-table">
            <thead>
                <tr>
                    <th>{{ Lang.seo.redirects.from|default("Source") }}</th>
                    <th>{{ Lang.seo.redirects.to|default("Destination") }}</th>
                    <th>{{ Lang.seo.redirects.code|default("Code") }}</th>
                    <th>{{ Lang.seo.redirects.hits|default("Hits") }}</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {% for r in redirects %}
                <tr>
                    <td>{{ r.old_url }}</td>
                    <td>{{ r.new_url }}</td>
                    <td>{{ r.code }}</td>
                    <td>{{ r.hits }} <small>({{ r.last_hit|default("-") }})</small></td>
                    <td>
                        <button type="submit" formaction="{{ ROUTER.generate("seo-admin-delete-redirect", {"id": r.id}) }}" class="btn-delete" onclick="return confirm('Supprimer?')"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>

    <div style="margin-top: 2rem; border-top: 1px solid #eee; padding-top: 1rem;">
        <button type="submit" class="button success">{{ Lang.submit }}</button>
    </div>
</form>

<script>
    function showTab(id) {
        document.querySelectorAll('.seo-tab-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.seo-tab').forEach(t => t.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        event.currentTarget.classList.add('active');
    }
</script>
