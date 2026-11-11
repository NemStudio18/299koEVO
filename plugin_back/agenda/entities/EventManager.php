<?php

namespace Agenda\Entities;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

class EventManager
{
    public function __construct()
    {
    }

    public function loadEvents(): array
    {
        $events = Event::query()->get();
        $data = array_map(fn($e) => $e->toArray(), $events);
        
        // Enricher avec les coordonnées des POIs si nécessaire (Optimisé en une seule requête)
        if (!empty($data)) {
            $poiIds = [];
            foreach ($data as $event) {
                if (!empty($event['poi_id']) && (empty($event['latitude']) || empty($event['longitude']))) {
                    $poiIds[] = (int)$event['poi_id'];
                }
            }

            if (!empty($poiIds)) {
                try {
                    // Use Provider Pattern to get POI coordinates
                    $provider = \Core\Core::getInstance()->callHook('entity:provider-request', ['entity' => 'poi']);
                    
                    if ($provider && method_exists($provider, 'getMany')) {
                        $poiDataList = $provider->getMany($poiIds);
                        
                        foreach ($data as &$event) {
                            if (!empty($event['poi_id']) && (empty($event['latitude']) || empty($event['longitude']))) {
                                $pId = (int)$event['poi_id'];
                                if (isset($poiDataList[$pId])) {
                                    $event['latitude'] = $poiDataList[$pId]['latitude'] ?? null;
                                    $event['longitude'] = $poiDataList[$pId]['longitude'] ?? null;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_WARNING, "[Agenda] Provider error: " . $e->getMessage());
                }
            }
        }
        
        return $data;
    }

    public function getAll(bool $showPast = false, bool $onlyApproved = false): array
    {
        $query = Event::query();
        
        if ($onlyApproved) {
            $query->where('status', '=', 'approved');
        }
        
        if (!$showPast) {
            $query->where('start_date', '>=', date('Y-m-d'));
        }

        $events = $query->orderBy('start_date', 'ASC')->get();
        return $events;
    }

    public function getById($id): ?Event
    {
        return Event::findPK($id);
    }

    public function getByDateRange(string $start, string $end): array
    {
        return Event::query()
            ->where('start_date', '>=', $start)
            ->where('start_date', '<=', $end)
            ->get();
    }

    public function create(array $data): ?Event
    {
        $event = new Event([
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'start_date' => $data['start_date'] ?? '',
            'end_date' => $data['end_date'] ?? $data['start_date'] ?? '',
            'location' => $data['location'] ?? '',
            'color' => $data['color'] ?? '#3498db',
            'status' => $data['status'] ?? 'approved',
            'created_by' => $data['created_by'] ?? null,
            'poi_id' => $data['poi_id'] ?? null,
            'proof_file' => $data['proof_file'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        if ($event->save()) {
            $newId = $event->getId();
            // Trigger event-created hook for other plugins (like PoiData activity tracker)
            try {
                if (!empty($data['poi_id'])) {
                    \Core\Core::getInstance()->callHook('agenda:event-created', [
                        'poi_id' => $data['poi_id'],
                        'event_id' => $newId
                    ]);
                }
            } catch (\Exception $e) {}

            // Trigger notification broadcast
            try {
                $router = \Core\Router\Router::getInstance();
                $linkUrl = null;
                try {
                    $linkUrl = $router->generate('agenda-event', ['id' => $newId]);
                } catch (\Exception $e) {
                    $linkUrl = '/agenda/event/' . $newId;
                }

                $notifData = [
                    'user_id' => 0, // Broadcast
                    'source_plugin' => 'agenda',
                    'entity_type' => 'event',
                    'entity_id' => $newId,
                    'notification_type' => 'new_event',
                    'title' => 'Nouvel événement à l\'agenda',
                    'message' => 'L\'événement "' . $event->getTitle() . '" a été ajouté pour le ' . date('d/m/Y', strtotime($event->getStartDate())),
                    'link_url' => $linkUrl
                ];
                \Core\Core::getInstance()->callHook('notifications:send', $notifData);

                // Email Alert to Admin
                $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('agenda');
                $adminEmail = $plugin->getConfigVal('notify_admin_email');
                if (!empty($adminEmail)) {
                    $core = \Core\Core::getInstance();
                    $subject = "[" . $core->getConfigVal('siteName') . "] Nouvel événement à l'agenda : " . $event->getTitle();
                    $message = "Un nouvel événement a été ajouté à l'agenda.\n\n";
                    $message .= "Titre : " . $event->getTitle() . "\n";
                    $message .= "Date : " . date('d/m/Y', strtotime($event->getStartDate())) . "\n";
                    $message .= "Lieu : " . ($event->getLocation() ?: 'Non spécifié') . "\n";
                    $message .= "Lien : " . rtrim($core->getConfigVal('siteUrl'), '/') . ($linkUrl ?? '/agenda');
                    
                    $headers = "From: " . $core->getConfigVal('siteName') . " <noreply@" . parse_url($core->getConfigVal('siteUrl'), PHP_URL_HOST) . ">";
                    @mail($adminEmail, $subject, $message, $headers);
                }
            } catch (\Exception $e) {
                \Core\Core::getInstance()->getLogger()->log('ERROR', "[Agenda] Erreur notification: " . $e->getMessage());
            }

            return $event;
        }
        return null;
    }

    public function update($id, array $data): bool
    {
        $event = Event::findPK($id);
        if ($event) {
            $event->fill($data);
            // Ensure end_date if only start_date changed
            if (isset($data['start_date']) && !isset($data['end_date'])) {
                $event->end_date = $data['start_date'];
            }
            return $event->save();
        }
        return false;
    }

    public function delete($id): bool
    {
        $event = Event::findPK($id);
        if ($event) {
            return $event->delete();
        }
        return false;
    }

    /**
     * Recherche globale : fournit les résultats de l'agenda
     */
    public function searchResults(string $query): array
    {
        $results = [];
        try {
            $events = $this->getAll(true, true); // Tous les événements approuvés (passés et futurs)
            
            foreach ($events as $event) {
                if (stripos($event->getTitle(), $query) !== false || stripos($event->getDescription(), $query) !== false) {
                    $results[] = [
                        'title' => $event->getTitle(),
                        'excerpt' => substr(strip_tags($event->getDescription()), 0, 150) . '...',
                        'url' => \Core\Router\Router::getInstance()->generate('agenda-event', ['id' => $event->getId()]),
                        'icon' => 'fa-solid fa-calendar-days',
                        'source' => 'agenda',
                        'source_label' => 'Agenda',
                        'date' => $event->getStartDate(),
                        'date_formatted' => date('d/m/Y', strtotime($event->getStartDate())),
                        'score' => (stripos($event->getTitle(), $query) !== false ? 10 : 0) + (stripos($event->getDescription(), $query) !== false ? 5 : 0)
                    ];
                }
            }
        } catch (\Exception $e) {
            // Ignorer
        }
        return $results;
    }
}

