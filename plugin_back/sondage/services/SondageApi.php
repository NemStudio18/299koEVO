<?php

namespace Sondage\Services;

use SondageManager;

defined('ROOT') or exit('No direct script access allowed');

class SondageApi
{
    /**
     * Retourne tous les sondages
     * @return array
     */
    public static function getAllPolls(): array
    {
        $manager = new SondageManager();
        $polls = $manager->getItems();
        $data = [];
        foreach ($polls as $poll) {
            $data[] = $poll->toArray();
        }
        return $data;
    }

    /**
     * Retourne les sondages actifs
     * @return array
     */
    public static function getActivePolls(): array
    {
        $manager = new SondageManager();
        $polls = $manager->getActiveItems();
        $data = [];
        foreach ($polls as $poll) {
            $data[] = $poll->toArray();
        }
        return $data;
    }

    /**
     * Retourne les résultats d'un sondage
     * @param int $id
     * @return array|null
     */
    public static function getPollResults($id): ?array
    {
        $manager = new SondageManager();
        $poll = $manager->create($id); // create() is actually find() in SondageManager based on previous code reading
        
        if (!$poll) {
            return null;
        }

        return $manager->getParticipationStats($poll);
    }
}
