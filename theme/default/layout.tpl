<!DOCTYPE html>
<html lang="{{lang.getLocale}}">
	<head>
		{% HOOK.frontHead %}
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<title>{{SHOW.titleTag}}</title>
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no"/>
		<meta name="description" content="{{SHOW.metaDescriptionTag}}"/>
		<link rel="icon" href="{{SHOW.themeIcon}}"/>
		<base href="{{SHOW.siteUrl}}/">
		{{SHOW.linkTags}}
		{{SHOW.scriptTags}}
		{{SHOW.showMetas}}
		{% HOOK.endFrontHead("") %}
	</head>
	<body class="{% if CORE.getPluginToCall %}plugin-{{ CORE.getPluginToCall|lower }} plugin-{{ CORE.getPluginToCall }} page-{{ CORE.getPluginToCall|lower }}{% endif %} {% if IS_LOGGED and USER.attributes.theme_preference == 'dark' %}theme-dark{% elseif IS_LOGGED and USER.attributes.theme_preference == 'light' %}theme-light{% endif %}" data-theme-save-url="{% HOOK.user:theme-save-url('') %}">
		<div id="container">
			<div id="header">
				<nav id="header_content">
					<div class="header-brand">
						<a href="{{SHOW.siteUrl}}" class="brand-logo">
							{% if CORE.getConfigVal("siteLogo") != "" and CORE.getConfigVal("siteLogoPlacement") == "siteName" %}
								<img class="site-logo site-logo-inline" src="{{SHOW.siteLogoUrl}}" alt="{{SHOW.siteName}}" />
							{% endif %}
							<p id="siteName">{{SHOW.siteName}}</p>
						</a>
					</div>
					<div class="nav-wrapper">
						<input type="checkbox" id="nav_toggle" class="nav-toggle" aria-hidden="true" />
						<div class="header-links">
							<ul id="navigation">
								{{SHOW.mainNavigation('<li><a class="[cssClass]" href="[target]" target="[targetAttribut]">[icon] [label]</a>[childrens]</li>')}}
								{% HOOK.endMainNavigation("") %}
								<li class="nav-search-item">
									{% HOOK.299search:navbar %}
								</li>
								{% if REGISTRATION_ENABLED and IS_LOGGED == false %}
									<li class="nav-auth-links">
										<a class="register-link" href="{% HOOK.user:register-url('') %}">{{Lang.core-register}}</a>
										<a class="login-link" href="{% HOOK.user:login-url('') %}">{{Lang.core-connection}}</a>
									</li>
								{% endif %}
								{% if IS_LOGGED %}
									{{SHOW.userAvatarMenu}}
								{% endif %}
							</ul>
						</div>
						<div class="header-utilities">
							{% HOOK.meteoWidgetHeader %}
							{% if CORE.getConfigVal("enable_theme_toggle") %}
								<button type="button" id="theme-toggle-btn" class="default-theme-toggle" title="Changer de thème (Auto / Sombre / Clair)" aria-label="Changer de thème">
									<span id="theme-icon" aria-hidden="true">🌓</span>
									<span id="theme-label">Auto</span>
								</button>
							{% endif %}
							<label for="nav_toggle" id="mobile_menu" aria-label="{{Lang.site-menu-label}}"><i class="fa-solid fa-bars"></i></label>
						</div>
					</div>
				</nav>
			</div>
			<div id="alert-msg">
				{{SHOW.displayMsg}}
			</div>
			{% HOOK.afterHeader %}
			{% if CORE.getConfigVal("showBanner") != false %}
			<div id="banner">
				<div id="siteDesc">
					{% if CORE.getConfigVal("siteLogo") != "" and CORE.getConfigVal("siteLogoPlacement") == "header" %}
						<img class="site-logo site-logo-banner" src="{{SHOW.siteLogoUrl}}" alt="{{SHOW.siteName}}" />
					{% endif %}
					{% if not CORE.getConfigVal("hideBannerTitle") %}
						<p class="banner-site-title">{{SHOW.siteName}}</p>
					{% endif %}
					{{SHOW.siteDesc}}
					{% HOOK.banner %}
				</div>
			</div>
			{% endif %}
		<main id="body">
			{% IF CORE.getConfigVal(hideTitles) == 0 and not HIDE_PAGE_TITLE %}
				<div id="pageTitle">
					{% if PAGE_TITLE %}
						<h1>{{PAGE_TITLE}}</h1>
					{% else %}
						{{SHOW.mainTitle}}
					{% endif %}
				</div>
			{% ENDIF %}
				<div id="body-page">
					<div id="content" class="{{SHOW.pluginId}}">
						{{CONTENT}}
					</div>
					{{show.displayPublicSidebar()}}
				</div>
			</main>
			<div id="footer">
				<div id="footer_content">
					{% HOOK.footer %}
					<p>
						<a target='_blank' href='https://299ko.ovh'>{{Lang.site-just-using( )}}</a>
						-
						{{Lang.site-theme}}
						{{SHOW.theme}}
						-
						{% if IS_LOGGED %}
							<a rel="nofollow" href="{% HOOK.user:logout-url('') %}">{{Lang.core-disconnection}}</a>
							{% if can_access('system:admin-access') %}
								-
								<a rel="nofollow" href="{% HOOK.admin:url('') %}">{{Lang.site-admin}}</a>
							{% endif %}
						{% else %}
							<a rel="nofollow" href="{% HOOK.user:login-url('') %}">{{Lang.core-connection}}</a>
						{% endif %}
						{% HOOK.endFooter %}
					</p>
						<div class="footer-nav">
							{{SHOW.footerNavigation}}
						</div>
				</div>
			</div>
		</div>
		{% HOOK.endFrontBody %}
        {# Theme Toggle Script #}
        {% if CORE.getConfigVal("enable_theme_toggle") %}
            <script src="{{Util.urlBuild("common/js/theme-toggle.js")}}"></script>
        {% endif %}
        {# HTMX Library #}
        <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    </body>
</html>
