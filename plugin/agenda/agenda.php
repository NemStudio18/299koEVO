<?php

namespace Agenda {

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public static function install(): void
    {
        $dataDir = DATA_PLUGIN . 'agenda';
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0755, true);
        }
        
        $eventsFile = $dataDir . '/events.json';
        if (!file_exists($eventsFile)) {
            file_put_contents($eventsFile, json_encode([], JSON_PRETTY_PRINT));
        }

        // Auto-create Agenda Page if not exists
        if (class_exists('\Core\Page\Page')) {
            $pageManager = new \Core\Page\Page();
            $existingPages = $pageManager->getItems();
            $agendaPageExists = false;
            foreach ($existingPages as $p) {
                if ($p->getTarget() === 'agenda') {
                    $agendaPageExists = true;
                    break;
                }
            }

            if (!$agendaPageExists) {
                $newPage = new \Core\Page\PageItem();
                $newPage->setName('Agenda');
                $newPage->setTarget('agenda'); // Sets targetIs() to 'plugin'
                $newPage->setTargetAttr('_self');
                $newPage->setPosition(100); // Put it at the end
                $newPage->setIsHidden(0);
                $newPage->setIsHomepage(0);
                $newPage->setNavPosition('menu');
                // $newPage->setLang('fr'); // Default value if needed, though PageItem doesn't seem to have setLang
                
                $pageManager->save($newPage);
            }
        }
    }
}

}

namespace {

    function agendaInstall(): void
    {
        \Agenda\Hooks::install();
    }

    function agendaBeforeRunPlugin(): void
    {
        // Charger les entités si nécessaire
        if (is_dir(__DIR__ . '/entities')) {
            foreach (glob(__DIR__ . '/entities/*.php') as $file) {
                require_once $file;
            }
        }
        
        // Charger les services si nécessaire
        if (is_dir(__DIR__ . '/services')) {
            foreach (glob(__DIR__ . '/services/*.php') as $file) {
                require_once $file;
            }
        }
        
        // Hooks are now registered in hooks.json
    }

    /**
     * Hook to contribute Agenda URLs to sitemap
     */
    function agendaCollectSitemapUrls(array $urls): array
    {
        if (class_exists('\Agenda\Entities\EventManager')) {
            $manager = new \Agenda\Entities\EventManager();
            $events = $manager->getAll(true, true); // Past & future, but only approved
            foreach ($events as $event) {
                $urls[] = '/agenda/event/' . $event->getId();
            }
        }
        return $urls;
    }

    /**
     * Hook pour la recherche globale
     */
    function agendaSearchResultsHook(array $data): array
    {
        $query = $data['query'];
        $manager = new \Agenda\Entities\EventManager();
        $results = $manager->searchResults($query);
        $data['results'] = array_merge($data['results'], $results);
        return $data;
    }

    /**
     * Injecte la feuille de style publique de l'agenda.
     */
    function agendaFrontHead(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        // Injecter seulement si on est sur une page agenda
        if (strpos($uri, '/agenda') === false && strpos($uri, '/admin/agenda') === false) {
            return '';
        }

        $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');
        $cssUrl = $siteUrl . '/plugin/agenda/template/public.css';
        
        $output = '<link rel="stylesheet" href="' . $cssUrl . '?v=' . VERSION . '">' . PHP_EOL;
        
        // Add attendance and map JS on event detail pages
        if (strpos($uri, '/agenda/event/') !== false && is_numeric(basename($uri))) {
            $jsUrl = $siteUrl . '/plugin/agenda/template/attendance.js';
            $output .= '<script src="' . $jsUrl . '?v=' . VERSION . '"></script>' . PHP_EOL;
            
            $mapJsUrl = $siteUrl . '/plugin/agenda/template/event-map.js';
            $output .= '<script src="' . $mapJsUrl . '?v=' . VERSION . '"></script>' . PHP_EOL;
        }

        // Add map picker on proposal/add/edit pages
        if (strpos($uri, '/agenda/event/propose') !== false || strpos($uri, '/agenda/event/add') !== false || strpos($uri, '/admin/agenda/events/edit') !== false || strpos($uri, '/admin/agenda/events/add') !== false) {
             // Load Leaflet via hook
             $output .= \Core\Core::getInstance()->callHook('map:load-assets');
             
             $pickerJsUrl = $siteUrl . '/plugin/agenda/template/event-form-map.js';
             $output .= '<script src="' . $pickerJsUrl . '?v=' . VERSION . '"></script>' . PHP_EOL;
        }

        // Add index maps JS on agenda list page
        if ($uri === '/agenda' || $uri === '/agenda/') {
            // Load Leaflet via hook
            $output .= \Core\Core::getInstance()->callHook('map:load-assets');
            
            $indexMapsJsUrl = $siteUrl . '/plugin/agenda/template/index-maps.js';
            $output .= '<script src="' . $indexMapsJsUrl . '?v=' . VERSION . '"></script>' . PHP_EOL;
        }
        
            // Add VanillaCalendar CSS and JS from CDN (Standard version for better compatibility)
            $output .= '<!-- VanillaCalendar Debug -->'.PHP_EOL;
            $output .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/build/vanilla-calendar.min.css">'.PHP_EOL;
            $output .= '<script src="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/build/vanilla-calendar.min.js"></script>'.PHP_EOL;
            
            // Initialize with extra diagnostics and event fetching
            $output .= '<script>
                (function() {
                    const API_URL = "' . $siteUrl . '/agenda/api/events";
                    
                    async function fetchEvents() {
                        try {
                            const response = await fetch(API_URL);
                            return await response.json();
                        } catch (e) {
                            console.error("[Agenda] Error fetching events:", e);
                            return [];
                        }
                    }

                    function generateColorsCSS(events) {
                        let css = "";
                        const seenColors = new Set();
                        if (Array.isArray(events)) {
                            events.forEach(ev => {
                                if (ev.color && !seenColors.has(ev.color)) {
                                    const safeColor = ev.color.replace("#", "");
                                    css += `.vc-event-${safeColor} { background-color: ${ev.color} !important; color: #fff !important; border-radius: 4px; }`;
                                    seenColors.add(ev.color);
                                }
                            });
                        }
                        const style = document.createElement("style");
                        style.innerHTML = css;
                        document.head.appendChild(style);
                    }

                    async function init() {
                        const el = document.getElementById("agenda-calendar");
                        if (!el) return;
                        
                        const Constructor = window.VanillaCalendar || window.VanillaCalendarPro;
                        if (!Constructor) {
                            setTimeout(init, 500);
                            return;
                        }

                        const events = await fetchEvents();
                        generateColorsCSS(events);

                        const popups = {};
                        if (Array.isArray(events)) {
                            events.forEach(ev => {
                                const date = ev.start.split(" ")[0];
                                if (!popups[date]) {
                                    popups[date] = {
                                        modifier: "vc-event-" + ev.color.replace("#", ""),
                                        html: `<div class="calendar-popup"><strong>${ev.title}</strong></div>`
                                    };
                                } else {
                                    popups[date].html += `<div class="calendar-popup"><strong>${ev.title}</strong></div>`;
                                }
                            });
                        }
                        
                        try {
                            const cal = new Constructor(el, {
                                settings: {
                                    lang: "fr",
                                    selection: { day: "single" },
                                    visibility: { theme: "light" }
                                },
                                popups: popups,
                                actions: {
                                    clickDay(e, self) {
                                        if (!self.selectedDates || self.selectedDates.length === 0) return;
                                        const date = self.selectedDates[0];
                                        const dayEvents = events.filter(ev => ev.start.startsWith(date));
                                        if (dayEvents.length === 1) {
                                            window.location.href = dayEvents[0].url;
                                        } else if (dayEvents.length > 1) {
                                            // Optional: handle multiple events on same day (maybe scroll to list or show choice)
                                            console.log("Multiple events on this day:", dayEvents);
                                        }
                                    }
                                }
                            });
                            cal.init();
                            console.log("[Agenda] Calendar initialized with " + events.length + " events");
                        } catch (e) {
                            console.error("[Agenda] Init error:", e);
                        }
                    }

                    window.addEventListener("load", init);
                    if (document.readyState === "complete") init();
                })();
            </script>' . PHP_EOL;
        
        echo $output;
        return '';
    }
    
    // Charger les entités et services dès l'inclusion du fichier
    // Cela garantit qu'elles sont disponibles avant l'instanciation des contrôleurs
    if (is_dir(__DIR__ . '/entities')) {
        foreach (glob(__DIR__ . '/entities/*.php') as $file) {
            require_once $file;
        }
    }
    if (is_dir(__DIR__ . '/services')) {
        foreach (glob(__DIR__ . '/services/*.php') as $file) {
            require_once $file;
        }
    }

    /**
     * Hook pour injecter le bouton "Ajouter un événement" dans le header de "Mes fiches"
     * @param array|null $claims Liste des claims (passée depuis le template)
     */
    function agendaClaimMyClaimsHeaderActions($claims = null): string
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('agenda');
        if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
            return '';
        }
        
        // Vérifier si $claims est fourni et non vide
        if (!is_array($claims) || empty($claims)) {
            return '';
        }
        
        $router = \Core\Router\Router::getInstance();
        try {
            $agendaEventAddUrl = $router->generate('agenda-event-add');
        } catch (\Exception $e) {
            $agendaEventAddUrl = '/agenda/event/add';
        }
        
        return '<a href="' . htmlspecialchars($agendaEventAddUrl) . '" class="button primary small">' .

               '<i class="fa-solid fa-calendar-plus"></i> Ajouter un événement</a>';
    }

    /**
     * Hook: navigation:catalog
     * Déclare l'entrée Agenda dans le catalogue du gestionnaire de menus (/admin/menus).
     */
    function agendaNavigationCatalog(array $catalog): array
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('agenda');
        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return $catalog;
        }

        $catalog[] = [
            'plugin'      => 'agenda',
            'key'         => 'agenda:agenda-index',
            'route'       => 'agenda-index',
            'label'       => 'Agenda',
            'icon'        => 'fa-solid fa-calendar',
            'description' => 'Calendrier des événements locaux',
        ];
        return $catalog;
    }

    /**
     * Hook: dashboard:inject-widgets
     * Injecte les prochains événements de l'agenda dans le dashboard
     */
    function agendaInjectDashboardWidgets($widgets)
    {
        if (!is_array($widgets)) {
            $widgets = [];
        }
        
        $router = \Core\Router\Router::getInstance();
        $nextEventsPrepared = [];
        
        try {
            if (\Core\Plugin\PluginsManager::isActivePlugin('agenda') && class_exists('\Agenda\Entities\EventManager')) {
                $eventManager = new \Agenda\Entities\EventManager();
                $allEvents = $eventManager->getAll(false, true); // Upcoming, Approved
                $nextEventsRaw = array_slice($allEvents, 0, 5);
                foreach ($nextEventsRaw as $k => $e) {
                    if (is_object($e)) {
                        $nextEventsPrepared[] = [
                             'title' => method_exists($e, 'getTitle') ? $e->getTitle() : ($e->title ?? ''),
                             'url' => method_exists($e, 'getUrl') ? $e->getUrl() : ($e->url ?? '#'),
                             'start_date' => method_exists($e, 'getStartDate') ? $e->getStartDate() : ($e->start_date ?? ''),
                             'location' => method_exists($e, 'getLocation') ? $e->getLocation() : ($e->location ?? ''),
                             'active' => ($k === 0),
                             'index' => $k,
                             'fallback' => false
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Erreur lors du chargement des événements
        }

        // Fallback si aucun événement
        if (empty($nextEventsPrepared)) {
            try {
                $url = $router->generate('agenda-index');
            } catch (\Exception $e) {
                $url = '#';
            }
            
            $nextEventsPrepared[] = [
                'title' => 'Aucun événement prévu',
                'url' => $url,
                'start_date' => 'À venir',
                'location' => 'Bourges',
                'fallback' => true
            ];
        }
        
        $widgets['nextEvents'] = $nextEventsPrepared;
        return $widgets;
    }


    /**
     * Hook: user:notification-preferences
     */
    function agendaUserProfileNotificationPreferences($user): string
    {
        $notifAgenda = $user->attributes['notif_agenda'] ?? true;
        
        return '
        <label class="setting-checkbox">
            <input type="checkbox" data-setting="notif_agenda" ' . ($notifAgenda ? 'checked' : '') . '>
            <span class="checkbox-label">Événements de l\'Agenda</span>
        </label>';
    }

    /**
     * Hook: user:profile-allowed-settings
     */
    function agendaUserProfileAllowedSettings(array $allowed): array
    {
        $allowed[] = 'notif_agenda';
        return $allowed;
    }

    function agendaIndexUrl($params = ''): string
    {
        try {
            return \Core\Router\Router::getInstance()->generate('agenda-index');
        } catch (\Exception $e) {
            return '#';
        }
    }

    /**
     * Hook: user:avatar-menu
     */
    function agendaUserAvatarMenu(array $data): array
    {
        return $data;
    }
}


