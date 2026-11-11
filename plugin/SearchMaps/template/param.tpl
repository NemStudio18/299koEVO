<section class="module-card">
    <header><i class="fa-solid fa-map-location-dot"></i> Configuration SearchMaps</header>

    <div class="tabs-container" data-tab-key="searchmaps">
        <ul class="tabs-header">
            <li class="default-tab" data-tab="config">
                <i class="fa-solid fa-sliders"></i> Paramètres
            </li>
            <li data-tab="help">
                <i class="fa-solid fa-circle-question"></i> Fonctionnement &amp; Intégration
            </li>
        </ul>

        <ul class="tabs">
            <!-- Tab 1: Paramètres -->
            <li class="tab">
                <form action="{{ router.generate('admin-SearchMaps-save') }}" method="POST">
                    <input type="hidden" name="_csrf" value="{{ csrfToken }}">

                    <div class="field">
                        <label for="zoom_default">Zoom par défaut (1 à 21)</label>
                        <input type="number" id="zoom_default" name="zoom_default" value="{{ zoomDefault }}" min="1" max="21">
                    </div>

                    <div class="field">
                        <label for="center_lat">Centre de la carte par défaut (Latitude)</label>
                        <input type="text" id="center_lat" name="center_lat" value="{{ centerLat }}">
                    </div>

                    <div class="field">
                        <label for="center_lon">Centre de la carte par défaut (Longitude)</label>
                        <input type="text" id="center_lon" name="center_lon" value="{{ centerLon }}">
                    </div>

                    <div class="field">
                        <label for="priority">Priorité d'affichage</label>
                        <input type="number" id="priority" name="priority" value="{{ priorityVal }}">
                    </div>

                    <div class="field">
                        <label>
                            <input type="checkbox" name="debug" {% if debugChecked %}checked{% endif %}> Activer le mode debug (logs navigateur <code>console.debug</code>)
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button primary"><i class="fa-solid fa-save"></i> Enregistrer la configuration</button>
                    </div>
                </form>
            </li>

            <!-- Tab 2: Fonctionnement & Intégration -->
            <li class="tab">
                <section>
                    <header><i class="fa-solid fa-wand-magic-sparkles"></i> 1. Utilisation rapide dans vos Pages et Articles (TinyMCE)</header>
                    <p>Dans l'éditeur de texte (Pages, Articles de Blog…), cliquez sur le bouton <strong>Insérer une carte</strong> dans la barre d'outils. Renseignez simplement la latitude, la longitude, le niveau de zoom et un titre optionnel : le shortcode est généré automatiquement à l'endroit du curseur.</p>
                </section>

                <section>
                    <header><i class="fa-solid fa-code"></i> 2. Syntaxe du shortcode universel <code>[map]</code></header>
                    <p>Vous pouvez aussi écrire ou modifier directement un shortcode <code>[map ...]</code> dans le contenu d'une page ou d'un article :</p>
                    <ul>
                        <li><strong>Carte avec paramètres par défaut :</strong> <code>[map]</code></li>
                        <li><strong>Carte centrée sur un lieu précis :</strong> <code>[map lat="47.0810" lon="2.3986" zoom="15" title="Point de rendez-vous"]</code></li>
                        <li><strong>Hauteur personnalisée :</strong> <code>[map lat="47.0810" lon="2.3986" zoom="14" title="Atelier" height="320"]</code> (en px)</li>
                    </ul>
                    <p><small>Si SearchMaps est désactivé ou désinstallé ultérieurement, le reste du site continue de fonctionner sans aucune erreur.</small></p>
                </section>

                <section>
                    <header><i class="fa-solid fa-sliders"></i> 3. Rôle des réglages &amp; cartes statiques</header>
                    <ul>
                        <li><strong>Zoom et Centre par défaut :</strong> utilisés automatiquement par <code>[map]</code> lorsqu'aucune coordonnée n'est précisée, et proposés comme valeurs initiales dans le bouton TinyMCE.</li>
                        <li><strong>Mode debug :</strong> active les traces <code>console.debug("[SearchMaps] Init map", ...)</code> dans la console développeur du navigateur lors du chargement d'une carte.</li>
                        <li><strong>Carte image statique :</strong> l'URL <code>/searchmaps/static?lat=47.0810&amp;lon=2.3986&amp;zoom=15&amp;width=600&amp;height=300</code> génère une image via OpenFreeMap (sans JavaScript).</li>
                    </ul>
                </section>
            </li>
        </ul>
    </div>
</section>

<style>
.field { margin-bottom: 1rem; }
.field label { display: block; margin-bottom: 0.4rem; font-weight: 600; }
.field input[type="number"], .field input[type="text"] { width: 100%; max-width: 420px; }
.form-actions { margin-top: 1.5rem; }
</style>
