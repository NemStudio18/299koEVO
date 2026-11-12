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
    <body class="theme-vegetal theme-light {% if CORE.getPluginToCall %}plugin-{{ CORE.getPluginToCall|lower }} plugin-{{ CORE.getPluginToCall }} page-{{ CORE.getPluginToCall|lower }}{% else %}page-home is-homepage{% endif %}">
        <div id="container" class="vegetal-shell">
            <header id="header" class="vegetal-header">
                <nav id="header_content" class="vegetal-nav" aria-label="Navigation principale">
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
                            <label for="nav_toggle" id="mobile_menu" aria-label="{{Lang.site-menu-label}}">
                                <i class="fa-solid fa-bars" aria-hidden="true"></i>
                            </label>
                        </div>
                    </div>
                </nav>
            </header>

            <div id="alert-msg" class="vegetal-alerts">
                {{SHOW.displayMsg}}
            </div>

            {% HOOK.afterHeader %}

            {% if CORE.getConfigVal("showBanner") != false %}
            <section id="banner" class="vegetal-banner">
                <div class="vegetal-banner-bg" aria-hidden="true">
                    <!-- Collines & relief végétal doux -->
                    <svg class="vegetal-banner-landscape" viewBox="0 0 1440 240" preserveAspectRatio="none" focusable="false">
                        <defs>
                            <linearGradient id="vgGradHillBack" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="var(--vg-accent)" stop-opacity="0.10" />
                                <stop offset="50%" stop-color="var(--vg-accent-light)" stop-opacity="0.08" />
                                <stop offset="100%" stop-color="var(--vg-accent)" stop-opacity="0.12" />
                            </linearGradient>
                            <linearGradient id="vgGradHillFront" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="var(--vg-accent)" stop-opacity="0.15" />
                                <stop offset="60%" stop-color="var(--vg-accent-light)" stop-opacity="0.12" />
                                <stop offset="100%" stop-color="var(--vg-warm)" stop-opacity="0.07" />
                            </linearGradient>
                        </defs>
                        <path class="vegetal-hill-back" d="M0,140 C340,70 680,180 1020,95 C1240,40 1380,110 1440,85 L1440,240 L0,240 Z" fill="url(#vgGradHillBack)" />
                        <path class="vegetal-hill-front" d="M0,180 C260,130 520,210 820,160 C1120,110 1320,185 1440,150 L1440,240 L0,240 Z" fill="url(#vgGradHillFront)" />
                    </svg>

                    <!-- Rameau végétal animé au vent (droite) -->
                    <svg class="vegetal-botanical-branch" viewBox="0 0 240 240" focusable="false">
                        <g class="vegetal-sway-group">
                            <path class="vg-stem" d="M120,240 Q130,150 160,50" fill="none" stroke="var(--vg-accent)" stroke-width="2.2" stroke-linecap="round" stroke-opacity="0.35" />
                            <path class="vg-leaf vg-leaf-top" d="M160,50 Q180,30 200,45 Q180,65 160,50 Z" fill="var(--vg-accent)" fill-opacity="0.22" stroke="var(--vg-accent)" stroke-width="1.2" stroke-opacity="0.4" />
                            <path class="vg-leaf vg-leaf-left1" d="M142,105 Q105,90 100,115 Q125,125 142,105 Z" fill="var(--vg-accent-light)" fill-opacity="0.25" stroke="var(--vg-accent)" stroke-width="1.2" stroke-opacity="0.4" />
                            <path class="vg-leaf vg-leaf-right1" d="M148,135 Q185,125 190,150 Q165,160 148,135 Z" fill="var(--vg-accent)" fill-opacity="0.20" stroke="var(--vg-accent)" stroke-width="1.2" stroke-opacity="0.35" />
                            <path class="vg-leaf vg-leaf-left2" d="M132,175 Q95,165 92,190 Q118,200 132,175 Z" fill="var(--vg-accent-light)" fill-opacity="0.22" stroke="var(--vg-accent)" stroke-width="1.2" stroke-opacity="0.35" />
                        </g>
                    </svg>

                    <!-- Feuilles flottantes portées par la brise -->
                    <svg class="vegetal-floating-leaf vg-float-1" viewBox="0 0 40 40" focusable="false">
                        <path d="M10,20 Q20,10 30,15 Q25,28 10,20 Z" fill="var(--vg-accent-light)" fill-opacity="0.30" />
                    </svg>
                    <svg class="vegetal-floating-leaf vg-float-2" viewBox="0 0 40 40" focusable="false">
                        <path d="M8,18 Q18,6 28,12 Q24,24 8,18 Z" fill="var(--vg-accent)" fill-opacity="0.20" />
                    </svg>
                </div>
                <div class="vegetal-banner-inner">
                    <div id="siteDesc" class="vegetal-site-desc">
                        {% if CORE.getConfigVal("siteLogo") != "" and CORE.getConfigVal("siteLogoPlacement") == "header" %}
                            <img class="site-logo site-logo-banner" src="{{SHOW.siteLogoUrl}}" alt="{{SHOW.siteName}}" />
                        {% endif %}
                        {% if not CORE.getConfigVal("hideBannerTitle") %}
                            <span class="vegetal-banner-kicker">{{SHOW.siteName}}</span>
                        {% endif %}
                        <div class="vegetal-tagline">{{SHOW.siteDesc}}</div>
                    </div>
                    {% HOOK.banner %}
                </div>
            </section>
            {% endif %}

            <main id="body" class="vegetal-main">
                {% IF CORE.getConfigVal(hideTitles) == 0 and not HIDE_PAGE_TITLE %}
                    <header id="pageTitle" class="vegetal-page-header">
                        {% if PAGE_TITLE %}
                            <h1>{{PAGE_TITLE}}</h1>
                        {% else %}
                            {{SHOW.mainTitle}}
                        {% endif %}
                    </header>
                {% ENDIF %}

                <div id="body-page" class="vegetal-grid">
                    <article id="content" class="vegetal-content {{SHOW.pluginId}}">
                        {{CONTENT}}
                    </article>
                    {{show.displayPublicSidebar()}}
                </div>
            </main>

            <footer id="footer" class="vegetal-footer">
                <div id="footer_content" class="vegetal-footer-inner">
                    {% HOOK.footer %}
                    <div class="vegetal-footer-row">
                        <p class="vegetal-footer-meta">
                            <span>{{SHOW.siteName}}</span>
                            <span class="vegetal-sep" aria-hidden="true">·</span>
                            <a target="_blank" rel="noopener" href="https://299ko.flexcb.fr">{{Lang.site-just-using( )}}</a>
                            <span class="vegetal-sep" aria-hidden="true">·</span>
                            <span>{{Lang.site-theme}} {{SHOW.theme}}</span>
                            <span class="vegetal-sep" aria-hidden="true">·</span>
                            {% if IS_LOGGED %}
                                <a rel="nofollow" href="{% HOOK.user:logout-url('') %}">{{Lang.core-disconnection}}</a>
                                {% if can_access('system:admin-access') %}
                                    <span class="vegetal-sep" aria-hidden="true">·</span>
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
        <script src="https://unpkg.com/htmx.org@1.9.10" defer></script>
    </body>
</html>

