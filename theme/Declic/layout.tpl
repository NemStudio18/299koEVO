<!DOCTYPE html>
<html lang="{{lang.getLocale}}">
    <head>
        {% HOOK.frontHead %}
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <title>{{SHOW.titleTag}}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <meta name="description" content="{{SHOW.metaDescriptionTag}}"/>
        <link rel="icon" href="{{SHOW.themeIcon}}"/>
        <base href="{{SHOW.siteUrl}}/"/>
        {{SHOW.linkTags}}
        {{SHOW.scriptTags}}
        {{SHOW.showMetas}}
        {% HOOK.endFrontHead("") %}
    </head>
    <body class="{% if CORE.getPluginToCall %}plugin-{{ CORE.getPluginToCall|lower }} plugin-{{ CORE.getPluginToCall }} page-{{ CORE.getPluginToCall|lower }}{% endif %} {% if IS_LOGGED and USER.attributes.theme_preference == 'dark' %}theme-dark{% elseif IS_LOGGED and USER.attributes.theme_preference == 'light' %}theme-light{% endif %}" data-theme-save-url="{% HOOK.user:theme-save-url('') %}">
        <div id="container" class="declic-shell">
            <header id="header" class="declic-header">
                <nav id="header_content" class="declic-nav" aria-label="Navigation principale">
                    <div class="header-brand">
                        <a href="{{SHOW.siteUrl}}" class="brand-logo">
                            {% if CORE.getConfigVal("siteLogo") != "" and CORE.getConfigVal("siteLogoPlacement") == "siteName" %}
                                <img class="site-logo site-logo-inline" src="{{SHOW.siteLogoUrl}}" alt="{{SHOW.siteName}}" />
                            {% endif %}
                            <span id="siteName" class="brand-title">{{SHOW.siteName}}</span>
                        </a>
                    </div>

                    <div class="nav-wrapper">
                        <input type="checkbox" id="nav_toggle" class="nav-toggle" aria-hidden="true" />
                        <div class="header-links">
                            <ul id="navigation">
                                {{SHOW.mainNavigation('<li><a class="[cssClass]" href="[target]" target="[targetAttribut]">[icon] [label]</a>[childrens]</li>')}}
                                {% HOOK.endMainNavigation("") %}
                                <li class="nav-search-item">{% HOOK.299search:navbar %}</li>
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
                            <button type="button" id="theme-toggle-btn" class="declic-theme-toggle" title="Changer de thème (Auto / Sombre / Clair)" aria-label="Changer de thème">
                                <span id="theme-icon" aria-hidden="true">🌓</span>
                                <span id="theme-label" class="declic-theme-label">Auto</span>
                            </button>
                            <label for="nav_toggle" id="mobile_menu" aria-label="{{Lang.site-menu-label}}">
                                <i class="fa-solid fa-bars" aria-hidden="true"></i>
                            </label>
                        </div>
                    </div>
                </nav>
            </header>

            <div id="alert-msg" class="declic-alerts">
                {{SHOW.displayMsg}}
            </div>

            {% HOOK.afterHeader %}

            {% if CORE.getConfigVal("showBanner") != false %}
            <section id="banner" class="declic-banner">
                <div class="declic-banner-bg" aria-hidden="true">
                    <svg class="declic-banner-grid" viewBox="0 0 1440 240" preserveAspectRatio="xMidYMid slice" focusable="false">
                        <defs>
                            <pattern id="declicGridPattern" width="48" height="48" patternUnits="userSpaceOnUse">
                                <path d="M 48 0 L 0 0 0 48" fill="none" stroke="var(--dc-border-strong)" stroke-width="0.7" stroke-opacity="0.32" />
                            </pattern>
                        </defs>
                        <rect width="1440" height="240" fill="url(#declicGridPattern)" />
                    </svg>
                    <svg class="declic-banner-wave declic-wave-1" viewBox="0 0 2880 240" preserveAspectRatio="none" focusable="false">
                        <defs>
                            <linearGradient id="declicWaveGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="var(--dc-accent)" stop-opacity="0.16" />
                                <stop offset="50%" stop-color="var(--dc-warm)" stop-opacity="0.11" />
                                <stop offset="100%" stop-color="var(--dc-accent)" stop-opacity="0.16" />
                            </linearGradient>
                        </defs>
                        <path d="M0,150 C240,85 480,215 720,150 C960,85 1200,215 1440,150 C1680,85 1920,215 2160,150 C2400,85 2640,215 2880,150 L2880,260 L0,260 Z" fill="url(#declicWaveGrad)" />
                    </svg>
                    <svg class="declic-banner-wave declic-wave-2" viewBox="0 0 2880 240" preserveAspectRatio="none" focusable="false">
                        <path d="M0,180 C240,225 480,135 720,180 C960,225 1200,135 1440,180 C1680,225 1920,135 2160,180 C2400,225 2640,135 2880,180 L2880,260 L0,260 Z" fill="var(--dc-accent)" fill-opacity="0.06" />
                        <path d="M0,180 C240,225 480,135 720,180 C960,225 1200,135 1440,180 C1680,225 1920,135 2160,180 C2400,225 2640,135 2880,180" fill="none" stroke="var(--dc-accent)" stroke-width="1.5" stroke-opacity="0.22" />
                    </svg>
                    <svg class="declic-banner-ring" viewBox="0 0 200 200" focusable="false">
                        <circle cx="100" cy="100" r="88" fill="none" stroke="var(--dc-accent)" stroke-width="1.2" stroke-opacity="0.22" stroke-dasharray="10 10" />
                        <circle cx="100" cy="100" r="62" fill="none" stroke="var(--dc-warm)" stroke-width="1" stroke-opacity="0.2" stroke-dasharray="4 8" />
                    </svg>
                </div>
                <div class="declic-banner-inner">
                    <div id="siteDesc" class="declic-site-desc">
                        {% if CORE.getConfigVal("siteLogo") != "" and CORE.getConfigVal("siteLogoPlacement") == "header" %}
                            <img class="site-logo site-logo-banner" src="{{SHOW.siteLogoUrl}}" alt="{{SHOW.siteName}}" />
                        {% endif %}
                        {% if not CORE.getConfigVal("hideBannerTitle") %}
                            <span class="declic-banner-kicker">{{SHOW.siteName}}</span>
                        {% endif %}
                        <div class="declic-tagline">{{SHOW.siteDesc}}</div>
                    </div>
                    {% HOOK.banner %}
                </div>
            </section>
            {% endif %}

            <main id="body" class="declic-main">
                {% IF CORE.getConfigVal(hideTitles) == 0 and not HIDE_PAGE_TITLE %}
                    <header id="pageTitle" class="declic-page-header">
                        {% if PAGE_TITLE %}
                            <h1>{{PAGE_TITLE}}</h1>
                        {% else %}
                            {{SHOW.mainTitle}}
                        {% endif %}
                    </header>
                {% ENDIF %}

                <div id="body-page" class="declic-grid">
                    <article id="content" class="declic-content {{SHOW.pluginId}}">
                        {{CONTENT}}
                    </article>
                    {{show.displayPublicSidebar()}}
                </div>
            </main>

            <footer id="footer" class="declic-footer">
                <div id="footer_content" class="declic-footer-inner">
                    {% HOOK.footer %}
                    <div class="declic-footer-row">
                        <p class="declic-footer-meta">
                            <span>{{SHOW.siteName}}</span>
                            <span class="declic-sep" aria-hidden="true">·</span>
                            <a target="_blank" rel="noopener" href="https://299ko.flexcb.fr">{{Lang.site-just-using( )}}</a>
                            <span class="declic-sep" aria-hidden="true">·</span>
                            <span>{{Lang.site-theme}} {{SHOW.theme}}</span>
                            <span class="declic-sep" aria-hidden="true">·</span>
                            {% if IS_LOGGED %}
                                <a rel="nofollow" href="{% HOOK.user:logout-url('') %}">{{Lang.core-disconnection}}</a>
                                {% if can_access('system:admin-access') %}
                                    <span class="declic-sep" aria-hidden="true">·</span>
                                    <a rel="nofollow" href="{% HOOK.admin:url('') %}">{{Lang.site-admin}}</a>
                                {% endif %}
                            {% else %}
                                <a rel="nofollow" href="{% HOOK.user:login-url('') %}">{{Lang.core-connection}}</a>
                            {% endif %}
                            {% HOOK.endFooter %}
                        </p>
                        <nav class="footer-nav" aria-label="Navigation pied de page">
                            {{SHOW.footerNavigation}}
                        </nav>
                    </div>
                </div>
            </footer>
        </div>

        {% HOOK.endFrontBody %}
        <script src="{{Util.urlBuild("common/js/theme-toggle.js")}}"></script>
        <script src="https://unpkg.com/htmx.org@1.9.10" defer></script>
    </body>
</html>
