<?php

namespace Docs\Entities;

use Utils\Util;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Maxime Blanc <maximeblanc@flexcb.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * Gestionnaire des activités du Wiki
 * 
 * Gère l'enregistrement et l'affichage des dernières activités du wiki
 */
class WikiActivityManager
{
    private array $activities = [];

    public function __construct() {
        $this->loadActivities();
    }

    private function loadActivities() {
        $this->activities = WikiActivity::query()->orderBy('timestamp', 'DESC')->limit(50)->get();
    }

    public function addActivity(string $action, string $pageName, string $categoryName = '', int $pageId = 0) {
        $activity = new WikiActivity([
            'action' => $action,
            'pageName' => $pageName,
            'categoryName' => $categoryName,
            'pageId' => $pageId,
            'timestamp' => time(),
            'date' => date('Y-m-d H:i:s')
        ]);

        if ($activity->save()) {
            $this->loadActivities();
        }
    }

    public function getLastActivity() {
        return $this->activities[0] ?? null;
    }

    public function getFormattedLastActivity() {
        $activity = $this->getLastActivity();
        if (!$activity) {
            return null;
        }

        $date = Util::getDate($activity->getTimestamp());
        
        switch ($activity->getAction()) {
            case 'add':
                $text = "Ajout de la page <strong>{$activity->getPageName()}</strong>";
                break;
            case 'edit':
                $text = "Modification de la page <strong>{$activity->getPageName()}</strong>";
                break;
            case 'delete':
                $text = "Suppression de la page <strong>{$activity->getPageName()}</strong>";
                break;
            case 'welcome':
                $text = "Bienvenue dans la <strong>{$activity->getPageName()}</strong>";
                break;
            default:
                $text = "Action sur la page <strong>{$activity->getPageName()}</strong>";
        }

        if (!empty($activity->getCategoryName())) {
            $text .= " dans <em>{$activity->getCategoryName()}</em>";
        }

        $text .= " le {$date}";

        return [
            'text' => $text,
            'timestamp' => $activity->getTimestamp(),
            'date' => $date
        ];
    }

    public function getAllActivities() {
        return $this->activities;
    }
}