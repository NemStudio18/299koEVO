<?php

namespace PoiData\Services;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour formater les horaires d'ouverture de manière lisible (humaine)
 */
class OpeningHoursFormatter
{
    private static $days = [
        0 => 'lundi',
        1 => 'mardi',
        2 => 'mercredi',
        3 => 'jeudi',
        4 => 'vendredi',
        5 => 'samedi',
        6 => 'dimanche'
    ];

    /**
     * Formate un tableau d'horaires structurés en chaînes lisibles
     * 
     * @param array $openingHours Tableau d'horaires (day_of_week, open_time, close_time, is_closed)
     * @return array Liste de chaînes formatées (ex: ["Du lundi au vendredi de 08:00 à 12:00", ...])
     */
    public function format(array $openingHours): array
    {
        if (empty($openingHours)) {
            return [];
        }

        // Grouper par plages horaires identiques
        $groups = [];
        // Trier par jour et heure d'ouverture
        usort($openingHours, function($a, $b) {
            if ($a['day_of_week'] !== $b['day_of_week']) {
                return $a['day_of_week'] <=> $b['day_of_week'];
            }
            return $a['open_time'] <=> $b['open_time'];
        });

        foreach ($openingHours as $oh) {
            if ($oh['is_closed']) {
                continue;
            }
            
            $openTimeRaw = $oh['open_time'] ?? '';
            $closeTimeRaw = $oh['close_time'] ?? '';
            
            $open = $openTimeRaw ? substr($openTimeRaw, 0, 5) : '';
            $close = $closeTimeRaw ? substr($closeTimeRaw, 0, 5) : '';
            $key = $open . '-' . $close;
            
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = (int)$oh['day_of_week'];
        }

        if (empty($groups)) {
            return ["Fermé"];
        }

        $formatted = [];
        foreach ($groups as $timeRange => $days) {
            list($open, $close) = explode('-', $timeRange);
            $formatted[] = $this->formatDayGroup($days) . " de $open à $close";
        }

        return $formatted;
    }

    /**
     * Formate un groupe de jours (ex: [0, 1, 2, 3, 4] -> "Du lundi au vendredi")
     */
    private function formatDayGroup(array $days): string
    {
        sort($days);
        $count = count($days);
        
        if ($count === 1) {
            return "Le " . self::$days[$days[0]];
        }

        if ($count === 7) {
            return "Tous les jours";
        }

        // Vérifier si c'est une plage continue
        $isContinuous = true;
        for ($i = 0; $i < $count - 1; $i++) {
            if ($days[$i+1] !== $days[$i] + 1) {
                $isContinuous = false;
                break;
            }
        }

        if ($isContinuous && $count > 2) {
            return "Du " . self::$days[$days[0]] . " au " . self::$days[$days[$count-1]];
        }

        // Sinon, lister les jours
        $labels = [];
        foreach ($days as $d) {
            $labels[] = self::$days[$d];
        }

        if ($count === 2) {
            return "Le " . $labels[0] . " et " . $labels[1];
        }

        $last = array_pop($labels);
        return "Le " . implode(', ', $labels) . " et " . $last;
    }
}
