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
	<body>
		<div id="container">
			<div id="header">
				<nav id="header_content">
					<div class="header-brand">
						<a href="{{SHOW.siteUrl}}" class="brand-logo">
							{% set logoUrl = SHOW.getSiteLogoUrl %}
							{% set logoPlacement = SHOW.getSiteLogoPlacement %}
							{% if logoUrl and (logoPlacement == 'before' or logoPlacement == 'siteName' or logoPlacement == 'header') %}
								<img src="{{logoUrl}}" alt="{{SHOW.siteName}}" class="site-logo-img" />
							{% endif %}
							<p id="siteName">{{SHOW.siteName}}</p>
							{% if logoUrl and logoPlacement == 'after' %}
								<img src="{{logoUrl}}" alt="{{SHOW.siteName}}" class="site-logo-img" />
							{% endif %}
						</a>
					</div>
					<div class="nav-wrapper">
						<input type="checkbox" id="nav_toggle" class="nav-toggle" aria-hidden="true" />
						<label for="nav_toggle" id="mobile_menu" aria-label="{{Lang.site-menu-label}}"><i class="fa-solid fa-bars"></i></label>
						<div class="header-links">
							<ul id="navigation">
								{{SHOW.mainNavigation}}
								{% HOOK.endMainNavigation("") %}
								{% if REGISTRATION_ENABLED and IS_LOGGED == false %}
									<li class="nav-auth-links">
										<a class="register-link" href="{% HOOK.user:register-url('') %}">{{Lang.core-register}}</a>
										<a class="login-link" href="{% HOOK.user:login-url('') %}">{{Lang.core-connection}}</a>
									</li>
								{% endif %}
								{% if IS_LOGGED %}
									{{SHOW.userAvatarMenu}}
								{% endif %}
								<li class="nav-search-item">
									{{SHOW.headerSearch("header")}}
								</li>
							</ul>
							{% HOOK.meteoWidgetHeader %}
						</div>
					</div>
				</nav>
			</div>
			<div id="alert-msg">
				{{SHOW.displayMsg}}
			</div>
			{% HOOK.afterHeader %}
			<div id="banner">
			<div id="siteDesc">
				{{SHOW.siteDesc}}
				{% if PAGE_TITLE %}
					<h1>{{PAGE_TITLE}}</h1>
				{% else %}
					{{SHOW.mainTitle}}
				{% endif %}
				{% HOOK.banner %}
			</div>
            {% if CURRENT_URI == '' or CURRENT_URI == '/' %}
            <div class="feature-boxes">
                <div class="feature-box box-blue">
                    <div class="feature-header">
                        <i class="fa-regular fa-comment"></i>
                        <h3>Votre Voix</h3>
                        <i class="fa-solid fa-chevron-down btn-expand"></i>
                    </div>
                    <div class="feature-content">
                        {% if atelier_poll %}
                            <p>{{ atelier_poll.getTitle }}</p>
                            <a href="{% HOOK.sondage:read-url(atelier_poll) %}" class="btn-feature">Donner mon avis</a>
                        {% else %}
                            <p>Partagez vos idées pour votre quartier</p>
                            <a href="{% HOOK.sondage:home-url('') %}" class="btn-feature">Donner mon avis</a>
                        {% endif %}
                    </div>
                </div>
                <div class="feature-box box-dark">
                    <div class="feature-header">
                        <i class="fa-regular fa-calendar"></i>
                        <h3>Prochaines Rencontres</h3>
                        <i class="fa-solid fa-chevron-down btn-expand"></i>
                    </div>
                    <div class="feature-content">
                        {% if atelier_event %}
                            <p>{{ atelier_event.getTitle }} ({{ atelier_event.getStartDate }})</p>
                            <a href="{{ atelier_event.getUrl }}" class="btn-feature">Voir l'événement</a>
                        {% else %}
                            <p>Consultez l'agenda des réunions de quartier</p>
                            <a href="{% HOOK.agenda:index-url('') %}" class="btn-feature">Voir l'agenda</a>
                        {% endif %}
                    </div>
                </div>
                <div class="feature-box box-light">
                    <div class="feature-header">
                        <i class="fa-solid fa-chart-simple"></i>
                        <h3>Le Baromètre</h3>
                        <i class="fa-solid fa-chevron-down btn-expand"></i>
                    </div>
                    <div class="feature-content">
                        <p>Déjà <strong>{{ atelier_users_count }}</strong> membres engagés !</p>
                        <a href="{% HOOK.publicstats:index-url('') %}" class="btn-feature">Voir les stats</a>
                    </div>
                </div>
            </div>
            {% endif %}
		</div>
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
			</main>
			<div id="footer">
				<div id="footer_content">
					{% HOOK.footer %}
					<p>
						<a target='_blank' href='https://299ko.ovh'>{{Lang.site-just-using(VERSION)}}</a>
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
					</p>
						<div class="footer-nav">
							{{SHOW.footerNavigation}}
						</div>
						{% HOOK.endFooter %}
				</div>
			</div>
		</div>
		{% HOOK.endFrontBody %}
	</body>
</html>
