<?php

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Sondage\Entities\SondageManager;
use Sondage\Entities\Sondage;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class SondageAdminController extends AdminController
{
    public function list()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('sondage:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        $sondageManager = new SondageManager();
        $totalUsersCount = count(\Core\Auth\User::all());

        $this->runPlugin->setMainTitle(Lang::get('sondage-admin-list'));
        $this->runPlugin->setTitleTag(Lang::get('sondage-admin-list'));

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('sondage', 'admin-tabs');

        // Préparer les stats pour tous les sondages (chargement direct depuis JSON)
        $allSondagesStats = [];
        foreach ($sondageManager->getItems() as $sondage) {
            /** @var Sondage $sondage */
            $participation = $sondageManager->getParticipationStats($sondage);
            $stats = [
                'id' => $sondage->getId(),
                'title' => $sondage->getTitle(),
                'totalVotes' => $sondage->getTotalVotes(),
                'active' => $sondage->getActive(),
                'optionsStats' => [],
                'participation' => $participation,
                'participationRate' => ($totalUsersCount > 0) ? round(($sondage->getTotalVotes() / $totalUsersCount) * 100, 1) : 0,
                'top5Cities' => array_slice($participation['cities'], 0, 5, true),
                'top5Districts' => array_slice($participation['districts'], 0, 5, true),
                'optionsLabels' => [],
                'optionsVotes' => []
            ];
            
            if ($sondage->getTotalVotes() > 0) {
                $options = $sondage->getOptions();
                foreach ($options as $index => $option) {
                    $votesCount = $sondage->getVotesForOption($index);
                    $percentage = round(($votesCount / $sondage->getTotalVotes()) * 100, 1);
                    $stats['optionsStats'][] = [
                        'index' => $index,
                        'text' => $option,
                        'votes' => $votesCount,
                        'percentage' => $percentage
                    ];
                    $stats['optionsLabels'][] = $option;
                    $stats['optionsVotes'][] = $votesCount;
                }
                
                // Ajouter "Autre" si activé
                if ($sondage->getAllowOther()) {
                    $otherVotes = $sondage->getOtherVotes();
                    if (count($otherVotes) > 0) {
                        $otherCount = count($otherVotes);
                        $stats['optionsStats'][] = [
                            'index' => 'other',
                            'text' => 'Autre',
                            'votes' => $otherCount,
                            'percentage' => round(($otherCount / $sondage->getTotalVotes()) * 100, 1)
                        ];
                        $stats['optionsLabels'][] = 'Autre';
                        $stats['optionsVotes'][] = $otherCount;
                    }
                }
            }
            $allSondagesStats[] = $stats;
        }

        $tpl->set('sondageManager', $sondageManager);
        $tpl->set('totalUsersCount', $totalUsersCount);
        $tpl->set('addUrl', $this->router->generate('sondage-admin-edit'));
        $tpl->set('saveUrl', $this->router->generate('sondage-admin-save'));
        $tpl->set('token', $this->user->token);
        $tpl->set('allSondagesStats', $allSondagesStats);
        $response->addTemplate($tpl);
        return $response;
    }
    

    public function edit($id = null)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('sondage:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        $sondageManager = new SondageManager();
        
        if ($id !== null) {
            /** @var Sondage $sondage */
            $sondage = $sondageManager->create($id);
            if (!$sondage) {
                $this->core->error404();
            }
        } else {
            $sondage = new Sondage();
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('sondage', 'admin-edit');

        // Préparer les statistiques pour chaque option
        $optionsStats = [];
        if ($sondage->getId() && $sondage->getTotalVotes() > 0) {
            $options = $sondage->getOptions();
            foreach ($options as $index => $option) {
                $votesCount = $sondage->getVotesForOption($index);
                $percentage = round(($votesCount / $sondage->getTotalVotes()) * 100, 1);
                $optionsStats[] = [
                    'index' => $index,
                    'text' => $option,
                    'votes' => $votesCount,
                    'percentage' => $percentage
                ];
            }
        }

        // Formater la date de clôture pour les champs input date et time
        $closeDateFormatted = '';
        $closeTimeFormatted = '23:59';
        if ($sondage->getCloseDate()) {
            $timestamp = strtotime($sondage->getCloseDate());
            $closeDateFormatted = date('Y-m-d', $timestamp);
            $closeTimeFormatted = date('H:i', $timestamp);
        }
        
        $tpl->set('sondage', $sondage);
        $tpl->set('saveUrl', $this->router->generate('sondage-admin-save'));
        $tpl->set('listUrl', $this->router->generate('sondage-admin-list'));
        $tpl->set('optionsStats', $optionsStats);
        $tpl->set('closeDateFormatted', $closeDateFormatted);
        $tpl->set('closeTimeFormatted', $closeTimeFormatted);
        $tpl->set('token', $this->user->token);
        $response->addTemplate($tpl);
        return $response;
    }

    public function save()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('sondage:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        $sondageManager = new SondageManager();
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';

        if (!empty($id)) {
            /** @var Sondage $sondage */
            $sondage = $sondageManager->create($id);
            if (!$sondage) {
                Show::msg(Lang::get('core-item-not-edited'), 'error');
                return $this->list();
            }
        } else {
            $sondage = new Sondage();
        }

        $sondage->setTitle(filter_var($_POST['title'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        
        // Date de clôture (optionnelle)
        $closeDate = isset($_POST['closeDate']) && !empty($_POST['closeDate']) 
            ? date('Y-m-d H:i:s', strtotime($_POST['closeDate'] . ' ' . ($_POST['closeTime'] ?? '23:59'))) 
            : null;
        $sondage->setCloseDate($closeDate);
        
        // Récupérer les options
        $options = [];
        if (isset($_POST['options']) && is_array($_POST['options'])) {
            foreach ($_POST['options'] as $option) {
                $option = trim(filter_var($option, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
                if (!empty($option)) {
                    $options[] = $option;
                }
            }
        }
        $sondage->setOptions($options);
        
        $sondage->setActive(isset($_POST['active']) ? '1' : '0');
        $sondage->setIsMultipleChoice(isset($_POST['isMultipleChoice']) ? '1' : '0');
        $sondage->setAllowOther(isset($_POST['allowOther']) ? '1' : '0');

        if ($sondageManager->saveSondage($sondage)) {
            Show::msg(Lang::get('core-item-edited'), 'success');
        } else {
            Show::msg(Lang::get('core-item-not-edited'), 'error');
        }

        header('location:' . $this->router->generate('sondage-admin-list'));
        die();
    }

    public function delete($id, $token)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('sondage:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }

        if ($token !== $this->user->token) {
            Show::msg(Lang::get('core-invalid-token'), 'error');
            return $this->list();
        }

        $sondageManager = new SondageManager();
        /** @var Sondage $sondage */
        $sondage = $sondageManager->create($id);

        if ($sondage && $sondageManager->delSondage($sondage)) {
            Show::msg(Lang::get('core-item-deleted'), 'success');
        } else {
            Show::msg(Lang::get('core-item-not-deleted'), 'error');
        }

        header('location:' . $this->router->generate('sondage-admin-list'));
        die();
    }
}
