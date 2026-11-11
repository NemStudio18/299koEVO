<section class="module-card">
    <header>{{Lang.configmanager.name}}</header>
    <form id="configForm" method="post" action="{{link}}" autocomplete="off">
	{{SHOW.tokenField}}

	<div class='tabs-container'>
		<ul class="tabs-header">
			<li class="default-tab">
				<i class="fa-solid fa-gear"></i>
				{{Lang.configmanager-settings}}
      </li>
			<li>
				<i class="fa-solid fa-gears"></i>
				{{Lang.configmanager-advanced-settings}}
      </li>
      <li>
        <i class="fa-solid fa-gauge-high"></i>
        {{Lang.configmanager-cache-settings}}
      </li>
      <li>
        <i class="fa-solid fa-database"></i>
        Storage
      </li>
      <li>
        <i class="fa-solid fa-chart-line"></i>
        {{Lang.configmanager-telemetry-settings}}
      </li>
      <li>
        <i class="fa-solid fa-person-digging"></i>
        Maintenance
      </li>
		</ul>
    
		<ul class="tabs">
			<li class="tab">
				<p>
					<input {% if CORE.getConfigVal("hideTitles") %} checked {% endif %} type="checkbox" name="hideTitles" id="hideTitles"/>
					<label for="hideTitles">{{Lang.configmanager-hide-titles}}</label>
				</p>
				<p>
					<input {% if CORE.getConfigVal("showBanner") != false %} checked {% endif %} type="checkbox" name="showBanner" id="showBanner"/>
					<label for="showBanner">Afficher la section bannière</label>
					<br><small>Affiche ou masque la section bannière (#banner) sur le site</small>
				</p>
				<p>
					<input {% if not CORE.getConfigVal("hideBannerTitle") %} checked {% endif %} type="checkbox" name="showBannerTitle" id="showBannerTitle"/>
					<label for="showBannerTitle">Afficher le titre du site dans la bannière</label>
					<br><small>Affiche ou masque le nom du site au-dessus de la description dans l'en-tête (#banner)</small>
				</p>
				<p>
					<label for="defaultPlugin">{{Lang.configmanager-public-default-plugin}}</label>
					<select name="defaultPlugin">
						<option value="page" {% IF defaultPlugin == "page" %}selected{% ENDIF %}>{{Lang.page.name}}</option>
						{% FOR plugin IN pluginsManager.getPlugins %}
							{% IF plugin.getCOnfigVal("activate") && plugin.getIsCallableOnPublic %}
								<option {% IF plugin.getIsDefaultPlugin %} selected {% ENDIF %} value="{{plugin.getName}}">{{plugin.getInfoVal("name")}}</option>
							{% ENDIF %}
						{% ENDFOR %}
					</select>
				</p>
				<p>
					<input {% if CORE.getConfigVal("allowRegistrations") %}checked{% endif %} type="checkbox" name="allowRegistrations" id="allowRegistrations"/>
					<label for="allowRegistrations">{{Lang.configmanager-allow-registrations}}</label>
					<br><small>{{Lang.configmanager-allow-registrations-desc}}</small>
				</p>
				<p>
					<label for="registrationDefaultGroup">{{Lang.configmanager-registration-default-group}}</label>
					<select name="registrationDefaultGroup" id="registrationDefaultGroup">
						{% for group in registrationGroups %}
							<option value="{{group.slug}}" {% if group.slug == registrationDefaultGroup %}selected{% endif %}>{{group.name}}</option>
						{% endfor %}
					</select>
				</p>
				<p>
					<label for="registrationValidationMode">{{Lang.configmanager-registration-validation-mode}}</label>
					<select name="registrationValidationMode" id="registrationValidationMode">
						<option value="email" {% if CORE.getConfigVal("registrationValidationMode") == "email" or CORE.getConfigVal("registrationValidationMode") == "" %}selected{% endif %}>{{Lang.configmanager-registration-validation-mode-email}}</option>
						<option value="admin" {% if CORE.getConfigVal("registrationValidationMode") == "admin" %}selected{% endif %}>{{Lang.configmanager-registration-validation-mode-admin}}</option>
						<option value="none" {% if CORE.getConfigVal("registrationValidationMode") == "none" %}selected{% endif %}>{{Lang.configmanager-registration-validation-mode-none}}</option>
					</select>
					<br><small>{{Lang.configmanager-registration-validation-mode-desc}}</small>
				</p>
				<p>
					<input {% if CORE.getConfigVal("antispamLogin") %}checked{% endif %} type="checkbox" name="antispamLogin" id="antispamLogin"/>
					<label for="antispamLogin">{{Lang.configmanager-antispam-login}}</label>
					<br><small>{{Lang.configmanager-antispam-login-desc}}</small>
					{% if not antispamPluginActive %}
						<br><small class="cfg-warn-note"><i class="fa-solid fa-triangle-exclamation"></i> {{Lang.configmanager-antispam-inactive-note}}</small>
					{% endif %}
				</p>
				<p>
					<input {% if CORE.getConfigVal("antispamRegister") %}checked{% endif %} type="checkbox" name="antispamRegister" id="antispamRegister"/>
					<label for="antispamRegister">{{Lang.configmanager-antispam-register}}</label>
					<br><small>{{Lang.configmanager-antispam-register-desc}}</small>
					{% if not antispamPluginActive %}
						<br><small class="cfg-warn-note"><i class="fa-solid fa-triangle-exclamation"></i> {{Lang.configmanager-antispam-inactive-note}}</small>
					{% endif %}
				</p>
				<p>
					<label for="sessionLifetime">{{Lang.configmanager-session-lifetime}}</label>
					<select name="sessionLifetime" id="sessionLifetime">
						<option value="1440" {% if CORE.getConfigVal("sessionLifetime") == 1440 or CORE.getConfigVal("sessionLifetime") == "" %}selected{% endif %}>24 minutes (par défaut PHP)</option>
						<option value="3600" {% if CORE.getConfigVal("sessionLifetime") == 3600 %}selected{% endif %}>1 heure</option>
						<option value="86400" {% if CORE.getConfigVal("sessionLifetime") == 86400 %}selected{% endif %}>1 jour</option>
						<option value="604800" {% if CORE.getConfigVal("sessionLifetime") == 604800 or (CORE.getConfigVal("sessionLifetime") != "" and CORE.getConfigVal("sessionLifetime") != 1440 and CORE.getConfigVal("sessionLifetime") != 3600 and CORE.getConfigVal("sessionLifetime") != 86400) %}selected{% endif %}>7 jours (recommandé)</option>
						<option value="2592000" {% if CORE.getConfigVal("sessionLifetime") == 2592000 %}selected{% endif %}>30 jours</option>
					</select>
					<br><small>{{Lang.configmanager-session-lifetime-desc}}</small>
				</p>
				<p>
					<label for="siteName">{{Lang.configmanager-sitename}}</label>
					<input type="text" name="siteName" id="siteName" value="{{CORE.getConfigVal("siteName")}}"/>
				</p>
				<p>
					<label for="siteDesc">{{Lang.configmanager-sitedesc}}</label>
					<textarea class="editor" name="siteDesc" id="siteDesc">{{CORE.getConfigVal("siteDesc")}}</textarea>
				</p>
				<p>
					<label for="siteLogo">{{Lang.configmanager-theme-logo}}</label>
					<input type="text" name="siteLogo" id="siteLogo" value="{{CORE.getConfigVal("siteLogo")}}"/>
					<br><small>{{Lang.configmanager-theme-logo-desc}}</small>
				</p>
				<p>
					<label for="siteLogoPlacement">{{Lang.configmanager-theme-logo-placement}}</label>
					<select name="siteLogoPlacement" id="siteLogoPlacement">
						<option value="none" {% if CORE.getConfigVal("siteLogoPlacement") == "" or CORE.getConfigVal("siteLogoPlacement") == "none" %}selected{% endif %}>{{Lang.configmanager-theme-logo-placement-none}}</option>
						<option value="siteName" {% if CORE.getConfigVal("siteLogoPlacement") == "siteName" %}selected{% endif %}>{{Lang.configmanager-theme-logo-placement-site-name}}</option>
						<option value="header" {% if CORE.getConfigVal("siteLogoPlacement") == "header" %}selected{% endif %}>{{Lang.configmanager-theme-logo-placement-header}}</option>
					</select>
					<br><small>{{Lang.configmanager-theme-logo-placement-desc}}</small>
				</p>
				<p>
					<label for="siteLang">{{Lang.configmanager-sitelang}}</label>
					<select name="siteLang">
						{{langOptions}}
					</select>
				</p>
				<p>
					<label id="label_theme" for="theme">{{Lang.configmanager-theme}}</label>
					<div class="cfg-theme-row">
						<select name="theme" id="theme" class="cfg-theme-select">
							{% for th in themesList %}
								<option {% if CORE.getConfigVal("theme") == th.slug %} selected {% endif %} value="{{th.slug}}">{{th.name}}</option>
							{% endfor %}
						</select>
						<button type="button" id="themePreviewBtn" class="button">
							<i class="fa-solid fa-eye"></i> {{Lang.configmanager-theme-preview}}
						</button>
					</div>
					<small id="themeMetaLine" class="cfg-theme-meta"></small>
				</p>
			</li>
			<li class="tab">

                <p>
					<input {% if CORE.getConfigVal("enable_theme_toggle") %} checked {% endif %} type="checkbox" name="enable_theme_toggle" id="enable_theme_toggle"/>
					<label for="enable_theme_toggle">Activer le basculeur de thème (Sombre/Clair)</label>
                    <br><small>Affiche un bouton permettant aux utilisateurs de basculer entre le mode clair et sombre.</small>
				</p>
			<p>
					<label id='delCacheDesc'>{{Lang.configmanager-delete-cache-desc}}</label><br>
					<a aria-describedby="delCacheDesc" class="button" href="{{cacheClearLink}}">{{Lang.configmanager-delete-cache}}</a>
				</p>
				<p>
					<input {% if CORE.getConfigVal("debug") %} checked {% endif %} type="checkbox" name="debug"/>
					<label for="debug">{{Lang.configmanager-debug}}</label>
				</p>
				<p>
					<label>{{Lang.configmanager-siteurl}}</label><br>
					<input type="text" name="siteUrl" value="{{CORE.getConfigVal("siteUrl")}}"/>
				</p>
				<p>
					<label for="htaccess">{{Lang.configmanager-htaccess}}</label><br>
					<textarea id="htaccess" name="htaccess">{{CORE.getHtaccess}}</textarea>
				</p>
			</li>
      <li class="tab">
        <p>
            <input {% if CORE.getConfigVal("cache_enabled") %}checked{% endif %} type="checkbox" name="cache_enabled" id="cache_enabled" /> 
            <label for="cache_enabled">{{Lang.configmanager-cache-enabled}}</label>
            <br><small>{{Lang.configmanager-cache-enabled-desc}}</small>
        </p>
        <p>
            <label for="cache_duration">{{Lang.configmanager-cache-duration}}</label>
            <input type="number" name="cache_duration" id="cache_duration" value="{% if CORE.getConfigVal("cache_duration") %}{{CORE.getConfigVal("cache_duration")}}{% else %}3600{% endif %}" min="60" max="86400" />
            <br><small>{{Lang.configmanager-cache-duration-desc}}</small>
        </p>
        <p>
            <input {% if CORE.getConfigVal("cache_minify") %}checked{% endif %} type="checkbox" name="cache_minify" id="cache_minify" /> 
            <label for="cache_minify">{{Lang.configmanager-cache-minify}}</label>
            <br><small>{{Lang.configmanager-cache-minify-desc}}</small>
        </p>
        <p>
            <input {% if CORE.getConfigVal("cache_lazy_loading") %}checked{% endif %} type="checkbox" name="cache_lazy_loading" id="cache_lazy_loading" /> 
            <label for="cache_lazy_loading">{{Lang.configmanager-cache-lazy-loading}}</label>
            <br><small>{{Lang.configmanager-cache-lazy-loading-desc}}</small>
        </p>
        <p>
            <label id="cacheClearDesc">{{Lang.configmanager-cache-clear-desc}}</label><br>
            <a aria-describedby="cacheClearDesc" class="button" href="{{cacheClearLink}}">{{Lang.configmanager-cache-clear}}</a>
        </p>

        {% if cacheStats %}
          <div class="cache-stats">
             <p><strong>{{Lang.configmanager-cache-files-count}}:</strong> {{cacheStats.files_count}}</p>
             <p><strong>{{Lang.configmanager-cache-total-size}}:</strong> {{cacheStats.total_size_formatted}}</p>
        </div>
        {% endif %}
      </li>
      <li class="tab">
          <p>
              <strong>Configuration du Stockage & Bases de données</strong>
              <br><small>La configuration de la base de données principale et des connexions SQL est désormais centralisée dans le module de gestion du stockage.</small>
          </p>
          <div class="cfg-storage-cta">
              <a href="{ ROUTER.generate('storage-admin') }" class="button primary">
                  <i class="fa-solid fa-database"></i> Accéder à la Gestion du Stockage
              </a>
          </div>
          {% if dbError %}
          <div class="msg error">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <strong>Erreur de connexion détectée</strong>: <code>{{dbError}}</code>
          </div>
          {% endif %}
      </li>
      <li class="tab">
        <p>
          <strong>{{Lang.configmanager-telemetry-title}}</strong>
          <br><small>{{Lang.configmanager-telemetry-desc}}</small>
        </p>
        <p>
          <input type="radio" name="telemetry_level" id="telemetry_level_0" value="0" {% if telemetryLevel == 0 %}checked{% endif %}/>
          <label for="telemetry_level_0">{{Lang.configmanager-telemetry-level-0}}</label>
          <br><small>{{Lang.configmanager-telemetry-level-0-desc}}</small>
        </p>
        <p>
          <input type="radio" name="telemetry_level" id="telemetry_level_1" value="1" {% if telemetryLevel == 1 %}checked{% endif %}/>
          <label for="telemetry_level_1">{{Lang.configmanager-telemetry-level-1}}</label>
          <br><small>{{Lang.configmanager-telemetry-level-1-desc}}</small>
        </p>
        <p>
          <input type="radio" name="telemetry_level" id="telemetry_level_2" value="2" {% if telemetryLevel == 2 %}checked{% endif %}/>
          <label for="telemetry_level_2">{{Lang.configmanager-telemetry-level-2}}</label>
          <br><small>{{Lang.configmanager-telemetry-level-2-desc}}</small>
        </p>
        {% if installationId %}
        <p>
          <strong>{{Lang.configmanager-telemetry-installation-id}}:</strong>
          <br><code class="cfg-install-id">{{installationId}}</code>
          <br><small>{{Lang.configmanager-telemetry-installation-id-desc}}</small>
        </p>
        {% endif %}
        {% if lastSync %}
        <p>
          <strong>{{Lang.configmanager-telemetry-last-sync}}:</strong>
          <br>{{lastSync}}
        </p>
        {% else %}
        <p>
          <strong>{{Lang.configmanager-telemetry-last-sync}}:</strong>
          <br><span class="cfg-danger-note">{{Lang.configmanager-telemetry-never-synced}}</span>
        </p>
        {% endif %}
        <p>
          <a href="{{forceSyncUrl}}" class="button">
            <i class="fa-solid fa-sync"></i> {{Lang.configmanager-telemetry-force-sync}}
          </a>
          <br><small>{{Lang.configmanager-telemetry-force-sync-desc}}</small>
        </p>
        <p>
          <a href="{ ROUTER.generate("configmanager-report") }" class="button">
            <i class="fa-solid fa-bug"></i> {{Lang.configmanager-report-title}}
          </a>
          <br><small>{{Lang.configmanager-report-desc-short}}</small>
        </p>
      </li>
      <li class="tab">
          <p>
              <strong>Mode Maintenance</strong>
              <br><small>Activez ce mode pour empêcher l'accès au site public pendant vos travaux.</small>
          </p>
          {% IF maintenanceMode %}
          <div class="msg warning cfg-maint-alert">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <strong>Le site est actuellement en maintenance.</strong>
              <br>Seuls les administrateurs peuvent y accéder.
          </div>
          {% ENDIF %}
          <p>
              <label for="maintenance_template">Modèle de page</label>
              <select name="maintenance_template" id="maintenance_template">
                  <option value="">-- Défaut (Système) --</option>
                  {% FOR tpl IN availableTemplates %}
                      <option value="{{tpl}}" {% IF maintenanceTemplate == tpl %}selected{% ENDIF %}>{{tpl}}</option>
                  {% ENDFOR %}
              </select>
              <br><small>Sélectionnez un fichier .tpl de votre thème pour l'affichage.</small>
          </p>
          <p>
              <input {% if maintenanceMode %} checked {% endif %} type="checkbox" name="maintenance_mode" id="maintenance_mode"/>
              <label for="maintenance_mode">Activer le mode maintenance</label>
          </p>
          <p>
              <label for="maintenance_message">Message de maintenance</label>
              <br><small>Ce message s'affichera sur la page de maintenance. HTML autorisé.</small>
              <textarea class="editor" name="maintenance_message" id="maintenance_message">{{maintenanceMessage}}</textarea>
          </p>
      </li>
		</ul>
	</div>
	<p>
		<button type="submit" class="button success">{{Lang.submit}}</button>
	</p>
</form>

<div id="themePreviewModal" class="cfg-preview-modal">
	<div class="cfg-preview-dialog">
		<div class="cfg-preview-head">
			<div class="cfg-preview-title-wrap">
				<i class="fa-solid fa-palette"></i>
				<strong id="themePreviewTitle">{{Lang.configmanager-theme-preview-title}}</strong>
			</div>
			<div class="cfg-preview-controls">
				<button type="button" id="themePreviewDesktopBtn" class="button small primary"><i class="fa-solid fa-desktop"></i> Bureau</button>
				<button type="button" id="themePreviewMobileBtn" class="button small"><i class="fa-solid fa-mobile-screen-button"></i> Mobile</button>
				<a id="themePreviewNewTab" href="#" target="_blank" class="button small"><i class="fa-solid fa-arrow-up-right-from-square"></i> Nouvel onglet</a>
				<button type="button" id="themePreviewCloseBtn" class="button small alert"><i class="fa-solid fa-xmark"></i> Fermer</button>
			</div>
		</div>
		<div class="cfg-preview-stage">
			<iframe id="themePreviewIframe" class="cfg-preview-iframe" src="about:blank" title="{{Lang.configmanager-theme-preview-title}}"></iframe>
		</div>
	</div>
</div>

<script>
(function() {
	const themesMeta = {{themesMetaJson}};
	const metaMap = {};
	(themesMeta || []).forEach(function(item) { metaMap[item.slug] = item; });

	const selectEl = document.getElementById('theme');
	const metaLine = document.getElementById('themeMetaLine');
	const previewBtn = document.getElementById('themePreviewBtn');
	const modal = document.getElementById('themePreviewModal');
	const iframe = document.getElementById('themePreviewIframe');
	const titleEl = document.getElementById('themePreviewTitle');
	const newTabEl = document.getElementById('themePreviewNewTab');
	const closeBtn = document.getElementById('themePreviewCloseBtn');
	const desktopBtn = document.getElementById('themePreviewDesktopBtn');
	const mobileBtn = document.getElementById('themePreviewMobileBtn');
	const siteUrl = window.location.origin;

	function refreshMeta() {
		if (!selectEl || !metaLine) return;
		const info = metaMap[selectEl.value];
		if (!info) {
			metaLine.textContent = '';
			return;
		}
		const parts = ['Dossier : theme/' + info.slug];
		if (info.version) parts.push('v' + info.version);
		if (info.author) parts.push(info.author);
		metaLine.textContent = parts.join(' • ');
	}

	function buildPreviewUrl(slug) {
		const base = (siteUrl || window.location.origin).replace(/\/$/, '');
		return base + '/?_theme_preview=' + encodeURIComponent(slug);
	}

	function openPreview() {
		if (!selectEl || !modal || !iframe) return;
		const slug = selectEl.value;
		const info = metaMap[slug];
		const url = buildPreviewUrl(slug);
		if (titleEl) titleEl.textContent = 'Aperçu en direct : ' + (info ? info.name : slug);
		if (newTabEl) newTabEl.href = url;
		iframe.src = url;
		iframe.classList.remove('is-mobile');
		modal.classList.add('is-open');
	}

	function closePreview() {
		if (!modal || !iframe) return;
		modal.classList.remove('is-open');
		iframe.src = 'about:blank';
	}

	if (selectEl) {
		selectEl.addEventListener('change', refreshMeta);
		refreshMeta();
	}
	if (previewBtn) previewBtn.addEventListener('click', openPreview);
	if (closeBtn) closeBtn.addEventListener('click', closePreview);
	if (modal) {
		modal.addEventListener('click', function(e) {
			if (e.target === modal) closePreview();
		});
	}
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && modal && modal.classList.contains('is-open')) closePreview();
	});
	if (desktopBtn && iframe) {
		desktopBtn.addEventListener('click', function() {
			iframe.classList.remove('is-mobile');
			desktopBtn.classList.add('primary');
			if (mobileBtn) mobileBtn.classList.remove('primary');
		});
	}
	if (mobileBtn && iframe) {
		mobileBtn.addEventListener('click', function() {
			iframe.classList.add('is-mobile');
			mobileBtn.classList.add('primary');
			if (desktopBtn) desktopBtn.classList.remove('primary');
		});
	}
})();
</script>
</section>
