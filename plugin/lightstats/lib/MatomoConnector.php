<?php
/**
 * MatomoConnector - Connecteur direct MySQL vers Matomo
 * Interroge la DB Matomo directement (bypass SSO YunoHost)
 */
namespace LightStats\Lib;
defined('ROOT') or exit('No direct script access allowed');

class MatomoConnector
{
    private static ?\PDO $pdo = null;
    const DB_HOST = '127.0.0.1';
    const DB_USER = 'matomo';
    const DB_PASS = '20f0b2cb4d0bdf440c3e85e5';
    const DB_NAME = 'matomo';
    const SITE_ID = 3;
    const MATOMO_URL = 'https://metriques.flexcb.fr/matomo/';

    private static function db(): \PDO {
        if (!self::$pdo) {
            self::$pdo = new \PDO(
                'mysql:host='.self::DB_HOST.';dbname='.self::DB_NAME.';charset=utf8mb4',
                self::DB_USER, self::DB_PASS,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        }
        return self::$pdo;
    }

    /** KPIs globaux */
    public static function getSummary(string $start, string $end): array {
        try {
            $s = self::db()->prepare("
                SELECT
                    COUNT(*)                                                    AS visits,
                    SUM(visit_total_actions)                                    AS pageviews,
                    COUNT(DISTINCT config_id)                                   AS unique_visitors,
                    ROUND(AVG(visit_total_time))                                AS avg_time_sec,
                    SUM(CASE WHEN visit_total_actions=1 THEN 1 ELSE 0 END)      AS bounces,
                    SUM(CASE WHEN visitor_returning=1 THEN 1 ELSE 0 END)        AS `returning`,
                    SUM(CASE WHEN visitor_returning=0 THEN 1 ELSE 0 END)        AS new_visitors,
                    SUM(CASE WHEN config_device_type='smartphone' OR config_device_type='tablet' THEN 1 ELSE 0 END) AS mobile,
                    SUM(CASE WHEN config_device_type='desktop' THEN 1 ELSE 0 END) AS desktop
                FROM matomo_log_visit
                WHERE idsite=:site AND visit_first_action_time>=:s AND visit_first_action_time<DATE_ADD(:e,INTERVAL 1 DAY)
            ");
            $s->execute([':site'=>self::SITE_ID,':s'=>$start,':e'=>$end]);
            $r = $s->fetch(\PDO::FETCH_ASSOC) ?: [];
            $v = (int)($r['visits']??0);
            $b = (int)($r['bounces']??0);
            $m = (int)($r['mobile']??0);
            return [
                'visits'          => $v,
                'pageviews'       => (int)($r['pageviews']??0),
                'unique_visitors' => (int)($r['unique_visitors']??0),
                'avg_time'        => self::dur((int)($r['avg_time_sec']??0)),
                'bounce_rate'     => $v>0 ? round($b/$v*100,1).'%' : '—',
                'returning'       => (int)($r['returning']??0),
                'new_visitors'    => (int)($r['new_visitors']??0),
                'mobile'          => $m,
                'desktop'         => (int)($r['desktop']??0),
                'mobile_pct'      => $v>0 ? round($m/$v*100,1).'%' : '—',
                'pages_per_visit' => $v>0 ? round((int)($r['pageviews']??0)/$v,1) : 0,
            ];
        } catch (\Exception $e) { return ['error'=>$e->getMessage()]; }
    }

    /** Visites par jour */
    public static function getByDay(string $start, string $end): array {
        try {
            $s = self::db()->prepare("
                SELECT DATE(visit_first_action_time) AS day, COUNT(*) AS visits, SUM(visit_total_actions) AS pv
                FROM matomo_log_visit
                WHERE idsite=:site AND visit_first_action_time>=:s AND visit_first_action_time<DATE_ADD(:e,INTERVAL 1 DAY)
                GROUP BY day ORDER BY day
            ");
            $s->execute([':site'=>self::SITE_ID,':s'=>$start,':e'=>$end]);
            return $s->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) { return []; }
    }

    /** Top pages */
    public static function getTopPages(string $start, string $end, int $limit=10): array {
        try {
            $s = self::db()->prepare("
                SELECT la.name AS url, COUNT(*) AS views
                FROM matomo_log_link_visit_action llva
                JOIN matomo_log_action la ON la.idaction=llva.idaction_url
                JOIN matomo_log_visit lv ON lv.idvisit=llva.idvisit
                WHERE lv.idsite=:site AND llva.server_time>=:s AND llva.server_time<DATE_ADD(:e,INTERVAL 1 DAY) AND la.type=1
                GROUP BY la.name ORDER BY views DESC LIMIT :lim
            ");
            $s->bindValue(':site',self::SITE_ID,\PDO::PARAM_INT);
            $s->bindValue(':s',$start); $s->bindValue(':e',$end);
            $s->bindValue(':lim',$limit,\PDO::PARAM_INT);
            $s->execute();
            return $s->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) { return []; }
    }

    /** Top référents */
    public static function getTopReferrers(string $start, string $end, int $limit=8): array {
        try {
            $s = self::db()->prepare("
                SELECT
                    COALESCE(NULLIF(referer_name,''),'(Direct / Bookmark)') AS source,
                    CASE referer_type WHEN 1 THEN 'Direct' WHEN 2 THEN 'Moteur de recherche' WHEN 3 THEN 'Site web' WHEN 6 THEN 'Campagne' ELSE 'Autre' END AS type,
                    COUNT(*) AS visits
                FROM matomo_log_visit
                WHERE idsite=:site AND visit_first_action_time>=:s AND visit_first_action_time<DATE_ADD(:e,INTERVAL 1 DAY)
                GROUP BY source,type ORDER BY visits DESC LIMIT :lim
            ");
            $s->bindValue(':site',self::SITE_ID,\PDO::PARAM_INT);
            $s->bindValue(':s',$start); $s->bindValue(':e',$end);
            $s->bindValue(':lim',$limit,\PDO::PARAM_INT);
            $s->execute();
            return $s->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) { return []; }
    }

    /** Top navigateurs */
    public static function getTopBrowsers(string $start, string $end): array {
        try {
            $s = self::db()->prepare("
                SELECT COALESCE(NULLIF(config_browser_name,''),'Inconnu') AS browser, COUNT(*) AS visits
                FROM matomo_log_visit
                WHERE idsite=:site AND visit_first_action_time>=:s AND visit_first_action_time<DATE_ADD(:e,INTERVAL 1 DAY)
                GROUP BY browser ORDER BY visits DESC LIMIT 8
            ");
            $s->execute([':site'=>self::SITE_ID,':s'=>$start,':e'=>$end]);
            return $s->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) { return []; }
    }

    /** Top OS */
    public static function getTopOs(string $start, string $end): array {
        try {
            $s = self::db()->prepare("
                SELECT COALESCE(NULLIF(config_os,''),'Inconnu') AS os, COUNT(*) AS visits
                FROM matomo_log_visit
                WHERE idsite=:site AND visit_first_action_time>=:s AND visit_first_action_time<DATE_ADD(:e,INTERVAL 1 DAY)
                GROUP BY os ORDER BY visits DESC LIMIT 8
            ");
            $s->execute([':site'=>self::SITE_ID,':s'=>$start,':e'=>$end]);
            return $s->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) { return []; }
    }

    /** Top pays */
    public static function getTopCountries(string $start, string $end): array {
        try {
            $s = self::db()->prepare("
                SELECT UPPER(COALESCE(NULLIF(location_country,''),'??')) AS country, COUNT(*) AS visits
                FROM matomo_log_visit
                WHERE idsite=:site AND visit_first_action_time>=:s AND visit_first_action_time<DATE_ADD(:e,INTERVAL 1 DAY)
                GROUP BY country ORDER BY visits DESC LIMIT 10
            ");
            $s->execute([':site'=>self::SITE_ID,':s'=>$start,':e'=>$end]);
            return $s->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) { return []; }
    }

    /** Mots-clés de recherche */
    public static function getTopKeywords(string $start, string $end): array {
        try {
            $s = self::db()->prepare("
                SELECT referer_keyword AS keyword, COUNT(*) AS visits
                FROM matomo_log_visit
                WHERE idsite=:site AND referer_keyword!='' AND referer_keyword IS NOT NULL
                  AND visit_first_action_time>=:s AND visit_first_action_time<DATE_ADD(:e,INTERVAL 1 DAY)
                GROUP BY referer_keyword ORDER BY visits DESC LIMIT 10
            ");
            $s->execute([':site'=>self::SITE_ID,':s'=>$start,':e'=>$end]);
            return $s->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) { return []; }
    }

    /** Pages d'entrée */
    public static function getEntryPages(string $start, string $end): array {
        try {
            $s = self::db()->prepare("
                SELECT la.name AS url, COUNT(*) AS entrances
                FROM matomo_log_visit lv
                JOIN matomo_log_action la ON la.idaction=lv.visit_entry_idaction_url
                WHERE lv.idsite=:site AND lv.visit_first_action_time>=:s AND lv.visit_first_action_time<DATE_ADD(:e,INTERVAL 1 DAY)
                GROUP BY la.name ORDER BY entrances DESC LIMIT 8
            ");
            $s->execute([':site'=>self::SITE_ID,':s'=>$start,':e'=>$end]);
            return $s->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) { return []; }
    }

    /**
     * Statistiques spécifiques pour une liste de POIs (par slugs)
     * Utilisé pour le Dashboard Propriétaire / Espace Pro
     */
    public static function getPoiStats(array $slugs, string $start, string $end): array {
        if (empty($slugs)) {
            return [
                'pageviews'        => 0,
                'unique_visitors'  => 0,
                'avg_time_spent'   => '0s',
                'avg_time_sec'     => 0,
                'phone_calls'      => 0,
                'website_clicks'   => 0,
                'directions_clicks'=> 0,
                'email_clicks'     => 0,
                'total_actions'    => 0,
                'daily_views'      => [],
                'daily_actions'    => []
            ];
        }

        try {
            $db = self::db();
            
            // Construction des clauses LIKE pour les URLs des slugs
            $urlClauses = [];
            $urlParams = [':site' => self::SITE_ID, ':s' => $start, ':e' => $end];
            foreach ($slugs as $i => $slug) {
                $p = ":slug_{$i}";
                $urlClauses[] = "la.name LIKE {$p}";
                $urlParams[$p] = "%/{$slug}%";
            }
            $urlWhere = '(' . implode(' OR ', $urlClauses) . ')';

            // 1. Vues & Visiteurs uniques sur les fiches POI
            $sqlViews = "
                SELECT
                    COUNT(*)                                          AS pageviews,
                    COUNT(DISTINCT llva.idvisit)                      AS unique_visitors,
                    ROUND(AVG(NULLIF(llva.time_spent_ref_action, 0))) AS avg_time_sec
                FROM matomo_log_link_visit_action llva
                JOIN matomo_log_action la ON la.idaction = llva.idaction_url
                JOIN matomo_log_visit lv ON lv.idvisit = llva.idvisit
                WHERE lv.idsite = :site
                  AND llva.server_time >= :s AND llva.server_time < DATE_ADD(:e, INTERVAL 1 DAY)
                  AND la.type = 1
                  AND {$urlWhere}
            ";
            $stmt = $db->prepare($sqlViews);
            $stmt->execute($urlParams);
            $rowViews = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            $pv = (int)($rowViews['pageviews'] ?? 0);
            $uv = (int)($rowViews['unique_visitors'] ?? 0);
            $avgSec = (int)($rowViews['avg_time_sec'] ?? 0);

            // 2. Évolution quotidienne des vues
            $sqlDailyViews = "
                SELECT DATE(llva.server_time) AS day, COUNT(*) AS views
                FROM matomo_log_link_visit_action llva
                JOIN matomo_log_action la ON la.idaction = llva.idaction_url
                JOIN matomo_log_visit lv ON lv.idvisit = llva.idvisit
                WHERE lv.idsite = :site
                  AND llva.server_time >= :s AND llva.server_time < DATE_ADD(:e, INTERVAL 1 DAY)
                  AND la.type = 1
                  AND {$urlWhere}
                GROUP BY day ORDER BY day
            ";
            $stmt = $db->prepare($sqlDailyViews);
            $stmt->execute($urlParams);
            $dailyViews = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // 3. Actions / Contacts déclenchés (Téléphone, Site, Itinéraire, Email)
            $actionClauses = [];
            $actionParams = [':site' => self::SITE_ID, ':s' => $start, ':e' => $end];
            foreach ($slugs as $i => $slug) {
                $p = ":aslug_{$i}";
                $actionClauses[] = "aname.name LIKE {$p}";
                $actionParams[$p] = "%{$slug}%";
            }
            $actionWhere = '(' . implode(' OR ', $actionClauses) . ')';

            $sqlActions = "
                SELECT
                    aact.name AS action_type,
                    COUNT(*)  AS count
                FROM matomo_log_link_visit_action llva
                JOIN matomo_log_action aact  ON aact.idaction = llva.idaction_event_action
                JOIN matomo_log_action aname ON aname.idaction = llva.idaction_name
                JOIN matomo_log_visit lv ON lv.idvisit = llva.idvisit
                WHERE lv.idsite = :site
                  AND llva.server_time >= :s AND llva.server_time < DATE_ADD(:e, INTERVAL 1 DAY)
                  AND {$actionWhere}
                GROUP BY aact.name
            ";
            $stmt = $db->prepare($sqlActions);
            $stmt->execute($actionParams);
            $actions = ['phone_call' => 0, 'website_click' => 0, 'directions_click' => 0, 'email_click' => 0];
            while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $t = $r['action_type'];
                if (isset($actions[$t])) {
                    $actions[$t] = (int)$r['count'];
                }
            }

            $totalActions = array_sum($actions);

            return [
                'pageviews'         => $pv,
                'unique_visitors'   => $uv,
                'avg_time_spent'    => self::dur($avgSec),
                'avg_time_sec'      => $avgSec,
                'phone_calls'       => $actions['phone_call'],
                'website_clicks'    => $actions['website_click'],
                'directions_clicks' => $actions['directions_click'],
                'email_clicks'      => $actions['email_click'],
                'total_actions'     => $totalActions,
                'daily_views'       => $dailyViews,
            ];
        } catch (\Exception $e) {
            return [
                'error'             => $e->getMessage(),
                'pageviews'         => 0,
                'unique_visitors'   => 0,
                'avg_time_spent'    => '0s',
                'avg_time_sec'      => 0,
                'phone_calls'       => 0,
                'website_clicks'    => 0,
                'directions_clicks' => 0,
                'email_clicks'      => 0,
                'total_actions'     => 0,
                'daily_views'       => []
            ];
        }
    }

    private static function dur(int $s): string {
        if ($s<=0) return '0s';
        $m=intdiv($s,60); $sec=$s%60;
        return $m>0 ? "{$m}m {$sec}s" : "{$sec}s";
    }
}
