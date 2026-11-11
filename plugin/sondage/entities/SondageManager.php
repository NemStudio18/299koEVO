<?php
namespace Sondage\Entities;

use Sondage\Entities\Sondage;
use Utils\Util;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class SondageManager
{
    private $items = [];

    public function __construct()
    {
        $this->loadItems();
    }

    private function loadItems()
    {
        $this->items = Sondage::query()->get();
    }

    /**
     * @return Sondage[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return Sondage[]
     */
    public function getActiveItems()
    {
        return array_filter($this->items, function($item) {
            /** @var Sondage $item */
            return $item->getActive();
        });
    }

    /**
     * @param mixed $id
     * @return Sondage|null
     */
    public function create($id)
    {
        return Sondage::findPK($id);
    }

    public function saveSondage($obj)
    {
        /** @var Sondage $obj */
        $id = $obj->getId();
        $isNew = empty($id);
        $wasActive = false;
        
        if (!$isNew) {
            /** @var Sondage $old */
            $old = $this->create($id);
            if ($old) {
                $wasActive = $old->getActive();
            }
        }

        $saved = $obj->save();
        
        if ($saved) {
            $this->loadItems();
            if ($obj->getActive() && !$wasActive) {
                // Trigger notification broadcast
                try {
                    $router = \Core\Router\Router::getInstance();
                    $linkUrl = null;
                    try {
                        $linkUrl = $router->generate('sondage-read', ['id' => $obj->getId()]);
                    } catch (\Exception $e) {
                        $linkUrl = '/sondage/' . $obj->getId();
                    }

                    $data = [
                        'user_id' => 0, // Broadcast
                        'source_plugin' => 'sondage',
                        'entity_type' => 'poll',
                        'entity_id' => $obj->getId(),
                        'notification_type' => 'new_poll',
                        'title' => 'Nouveau sondage disponible',
                        'message' => 'Un nouveau sondage est ouvert : "' . $obj->getTitle() . '". Votre avis compte !',
                        'link_url' => $linkUrl
                    ];
                    \Core\Core::getInstance()->callHook('notifications:send', $data);
                } catch (\Exception $e) {
                    \Core\Core::getInstance()->getLogger()->log('ERROR', "[Sondage] Erreur notification: " . $e->getMessage());
                }
            }
        }
        
        return $saved;
    }

    public function delSondage($obj)
    {
        if ($obj->delete()) {
            $this->loadItems();
            return true;
        }
        return false;
    }

    public function getParticipationStats($sondage)
    {
        $stats = [
            'cities' => [],
            'districts' => [],
            'ages' => [
                'age_minus_18' => 0,
                'age_18_25' => 0,
                'age_26_35' => 0,
                'age_36_50' => 0,
                'age_51_65' => 0,
                'age_65_plus' => 0,
                'age_unknown' => 0
            ],
            'sexes' => [
                'M' => 0,
                'F' => 0,
                'O' => 0,
                'unknown' => 0
            ]
        ];

        if ($sondage->getTotalVotes() > 0) {
            $allUsers = \Core\Auth\User::all();
            $usersById = [];
            foreach ($allUsers as $u) {
                $usersById[(string)$u->attributes['id']] = $u;
            }

            foreach ($sondage->getVotes() as $vote) {
                $userId = isset($vote['userId']) ? (string)$vote['userId'] : null;
                
                if ($userId && isset($usersById[$userId])) {
                    $u = $usersById[$userId];
                    
                    $city = $u->attributes['city'] ?? 'Inconnu';
                    if (empty($city)) $city = 'Inconnu';
                    $stats['cities'][$city] = ($stats['cities'][$city] ?? 0) + 1;

                    $district = $u->attributes['district'] ?? 'Inconnu';
                    if (empty($district)) $district = 'Inconnu';
                    $stats['districts'][$district] = ($stats['districts'][$district] ?? 0) + 1;

                    $age = intval($u->attributes['age'] ?? 0);
                    if ($age === 0) $stats['ages']['age_unknown']++;
                    elseif ($age < 18) $stats['ages']['age_minus_18']++;
                    elseif ($age <= 25) $stats['ages']['age_18_25']++;
                    elseif ($age <= 35) $stats['ages']['age_26_35']++;
                    elseif ($age <= 50) $stats['ages']['age_36_50']++;
                    elseif ($age <= 65) $stats['ages']['age_51_65']++;
                    else $stats['ages']['age_65_plus']++;

                    $sex = $u->attributes['sex'] ?? 'unknown';
                    if (isset($stats['sexes'][$sex])) {
                        $stats['sexes'][$sex]++;
                    } else {
                        $stats['sexes']['unknown']++;
                    }
                } else {
                     $stats['cities']['Inconnu'] = ($stats['cities']['Inconnu'] ?? 0) + 1;
                     $stats['districts']['Inconnu'] = ($stats['districts']['Inconnu'] ?? 0) + 1;
                     $stats['ages']['age_unknown']++;
                     $stats['sexes']['unknown']++;
                }
            }
            arsort($stats['cities']);
            arsort($stats['districts']);
        }
        return $stats;
    }
}


