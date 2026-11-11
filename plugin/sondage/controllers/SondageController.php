<?php

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Utils\Show;
use Core\Lang;
use Utils\VoteProtection;
use Core\Auth\UsersManager;
use Core\Auth\User;
use Core\Plugin\PluginsManager;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class SondageController extends PublicController
{
    public function home()
    {
        $sondageManager = new SondageManager();
        $activeSondages = $sondageManager->getActiveItems();

        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-square-poll-vertical"></i> ' . Lang::get('sondage.name'));

        $tpl = $response->createPluginTemplate('sondage', 'list');

        $sondages = [];
        foreach ($activeSondages as $sondage) {
            $totalVotes = $sondage->getTotalVotes();
            $hasVoted = $this->hasUserVoted($sondage);
            $isClosed = $sondage->isClosed();
            
            // Préparer les stats seulement si le sondage est terminé
            $optionsStats = [];
            if ($isClosed && $totalVotes > 0) {
                $allOptions = $sondage->getOptions();
                foreach ($allOptions as $index => $option) {
                    $votes = $sondage->getVotesForOption($index);
                    $percentage = round(($votes / $totalVotes) * 100, 1);
                    $optionsStats[] = [
                        'text' => $option,
                        'votes' => $votes,
                        'percentage' => $percentage
                    ];
                }
            }
            
            $sondages[] = [
                'id' => $sondage->getId(),
                'title' => $sondage->getTitle(),
                'options' => $sondage->getOptions(),
                'totalVotes' => $totalVotes,
                'votesCountText' => Lang::get('sondage.votes-count', $totalVotes),
                'hasVoted' => $hasVoted,
                'isClosed' => $isClosed,
                'optionsStats' => $optionsStats,
                'url' => $this->router->generate('sondage-read', ['id' => $sondage->getId()])
            ];
        }

        $tpl->set('sondages', $sondages);
        $tpl->set('sondageManager', $sondageManager);
        $response->addTemplate($tpl);
        return $response;
    }

    public function read($id)
    {
        $sondageManager = new SondageManager();
        $sondage = $sondageManager->create($id);

        if (!$sondage || (!$sondage->getActive() && !$sondage->isClosed())) {
            $this->core->error404();
        }

        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-circle-question"></i> ' . $sondage->getTitle());

        $tpl = $response->createPluginTemplate('sondage', 'read');

        $optionsData = [];
        $allOptions = $sondage->getOptions();
        foreach ($allOptions as $index => $option) {
            $votes = $sondage->getVotesForOption($index);
            $total = $sondage->getTotalVotes();
            $percentage = $total > 0 ? round(($votes / $total) * 100, 1) : 0;
            
            $optionsData[] = [
                'index' => $index,
                'text' => $option,
                'votes' => $votes,
                'percentage' => $percentage
            ];
        }

        // Prepare chart data for main pie chart
        $chartLabels = [];
        $chartDataValues = [];
        foreach ($optionsData as $opt) {
            $chartLabels[] = $opt['text'];
            $chartDataValues[] = $opt['votes'];
        }

        $totalVotes = $sondage->getTotalVotes();
        $hasVoted = $this->hasUserVoted($sondage);
        $isClosed = $sondage->isClosed();
        
        // Données de participation (Démographie) via le manager
        $participationStats = $sondageManager->getParticipationStats($sondage);

        $tpl->set('sondage', $sondage);
        $tpl->set('optionsData', $optionsData);
        $tpl->set('hasVoted', $hasVoted);
        $tpl->set('isClosed', $isClosed);
        $tpl->set('participationStats', $participationStats);
        $tpl->set('jsonCities', json_encode($participationStats['cities'], JSON_UNESCAPED_UNICODE));
        $tpl->set('jsonDistricts', json_encode($participationStats['districts'], JSON_UNESCAPED_UNICODE));
        $tpl->set('jsonChartLabels', json_encode($chartLabels, JSON_UNESCAPED_UNICODE));
        $tpl->set('jsonChartData', json_encode($chartDataValues));
        $tpl->set('voteUrl', $this->router->generate('sondage-vote', ['id' => $sondage->getId()]));
        $tpl->set('allOptions', $allOptions);
        $tpl->set('totalVotesText', Lang::get('sondage.votes-count', $totalVotes));
        $tpl->set('otherVotes', $sondage->getOtherVotes());
        $tpl->set('listUrl', $this->router->generate('sondage-home'));
        $response->addTemplate($tpl);
        return $response;
    }

    public function vote($id)
    {
        $sondageManager = new SondageManager();
        $sondage = $sondageManager->create($id);

        if (!$sondage || !$sondage->getActive() || $sondage->isClosed()) {
            Show::msg(Lang::get('sondage.vote-error'), 'error');
            return $this->read($id);
        }

        // Vérifier si autorisé à voter
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext(UsersManager::getCurrentUser());
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('sondage:vote'), $ctx)) {
            Show::msg(Lang::get('sondage.require-login'), 'error');
            return $this->read($id);
        }

        // Vérifier si le profil est complet (Ville + Quartier)
        $user = UsersManager::getCurrentUser();
        if (empty($user->attributes['city']) || empty($user->attributes['district'])) {
            Show::msg("Merci de renseigner votre ville et votre quartier dans votre profil pour participer au vote.", 'warning');
            return $this->read($id);
        }

        // Vérifier si déjà voté
        if ($this->hasUserVoted($sondage)) {
            Show::msg(Lang::get('sondage.already-voted'), 'warning');
            return $this->read($id);
        }

        // Récupérer les options votées (index ou indices)
        $options = $_POST['options'] ?? $_POST['option'] ?? [];
        if (!is_array($options)) {
            $options = [$options];
        }
        
        $verifiedOptions = [];
        $availableOptionsCount = count($sondage->getOptions());
        foreach ($options as $opt) {
            $idx = intval($opt);
            if ($idx >= 0 && $idx < $availableOptionsCount) {
                $verifiedOptions[] = $idx;
            }
        }

        // Option "Autre"
        $otherText = null;
        if ($sondage->getAllowOther() && !empty($_POST['other_text'])) {
            $otherText = $_POST['other_text'];
        }

        if (empty($verifiedOptions) && $otherText === null) {
            Show::msg(Lang::get('sondage.vote-error'), 'error');
            return $this->read($id);
        }

        // Ajouter le vote
        $userId = UsersManager::getCurrentUser()->id;
        $sondage->addVote($verifiedOptions, $userId, $otherText);
        $sondageManager->saveSondage($sondage);

        Show::msg(Lang::get('sondage.vote-success'), 'success');
        return $this->read($id);
    }

    private function hasUserVoted($sondage)
    {
        $runPlugin = PluginsManager::getInstance()->getPlugin('sondage');
        $allowMultiple = $runPlugin->getConfigVal('allowMultipleVotes') == '1';
        
        if ($allowMultiple) {
            return false;
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext(UsersManager::getCurrentUser());
        $userId = $evaluator->canAccess(new \Core\Navigation\GenericResource('sondage:access'), $ctx) ? UsersManager::getCurrentUser()->id : null;
        $fingerprint = VoteProtection::getFingerprint();
        
        return $sondage->hasUserVoted($userId, $fingerprint);
    }
}
