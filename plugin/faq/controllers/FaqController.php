<?php

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Utils\Show;
use Core\Lang;
use Utils\VoteProtection;
use Core\Core;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class FaqController extends PublicController
{
    public function home()
    {
        $faqManager = new FaqManager();
        $category = filter_var($_GET['category'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $this->runPlugin->setMainTitle($this->runPlugin->getConfigVal('pageTitle') ?: Lang::get('faq.name'));
        $this->runPlugin->setTitleTag($this->runPlugin->getConfigVal('pageTitle') ?: Lang::get('faq.name'));

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('faq', 'list');

        $questions = $faqManager->getQuestionsByCategory($category);
        $categories = $faqManager->getCategories();
        
        $fingerprint = VoteProtection::getFingerprint();
        
        $questionsData = [];
        foreach ($questions as $q) {
            $questionsData[] = [
                'id' => $q->getId(),
                'question' => $q->getQuestion(),
                'answer' => $q->getAnswer(),
                'category' => $q->getCategory(),
                'votes' => $q->getVotes(),
                'hasVoted' => $q->hasVoted($fingerprint),
                'voteUrl' => $this->router->generate('faq-vote', ['id' => $q->getId()])
            ];
        }

        $antispam = ($this->pluginsManager->isActivePlugin('antispam')) ? new antispam() : false;
        $antispamField = ($antispam) ? $antispam->show() : '';

        $tpl->set('questions', $questionsData);
        $tpl->set('categories', $categories);
        $tpl->set('selectedCategory', $category);
        $tpl->set('askUrl', $this->router->generate('faq-ask'));
        $tpl->set('antispam', $antispam);
        $tpl->set('antispamField', $antispamField);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function vote($id)
    {
        $fingerprint = VoteProtection::getFingerprint();
        $faqManager = new FaqManager();
        $question = $faqManager->create($id);
        
        if (!$question || !$question->isActive()) {
            Show::msg(Lang::get('faq.invalid-question'), 'error');
            return $this->home();
        }

        if ($question->hasVoted($fingerprint)) {
            Show::msg(Lang::get('faq.already-voted'), 'error');
        } else {
            $question->addVote($fingerprint);
            $faqManager->saveQuestion($question);
            Show::msg(Lang::get('faq.vote-success'), 'success');
        }

        Core::getInstance()->redirect($this->router->generate('faq-home') . (isset($_GET['category']) ? '?category=' . urlencode($_GET['category']) : ''));
    }

    public function ask()
    {
        $antispam = ($this->pluginsManager->isActivePlugin('antispam')) ? new antispam() : false;
        
        if ($antispam && !$antispam->isValid()) {
            Show::msg(Lang::get('antispam.invalid-captcha'), 'error');
            return $this->home();
        }

        // Vérification du champ honeypot
        if (isset($_POST['_name']) && $_POST['_name'] !== '') {
            Show::msg(Lang::get('faq.error'), 'error');
            return $this->home();
        }

        $question = filter_var($_POST['question'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        
        if (empty($question)) {
            Show::msg(Lang::get('faq.empty-question'), 'error');
            return $this->home();
        }

        // Sauvegarder la question dans la base de données (inactive par défaut)
        $faqManager = new FaqManager();
        $faqQuestion = new FaqQuestion();
        $faqQuestion->setQuestion($question);
        $faqQuestion->setAnswer(''); // Réponse vide, à remplir par l'admin
        $faqQuestion->setCategory(''); // Pas de catégorie par défaut
        $faqQuestion->setOrder(0);
        $faqQuestion->setActive('0'); // Inactive par défaut, l'admin doit l'activer
        $faqQuestion->setEmail($email); // Stocker l'email pour contact si nécessaire
        $faqManager->saveQuestion($faqQuestion);
        
        // Envoyer l'email à l'administrateur
        $core = Core::getInstance();
        $siteEmail = $core->getConfigVal('siteEmail');
        $siteName = $core->getConfigVal('siteName');
        
        $subject = Lang::get('faq.new-question-subject');
        $message = Lang::get('faq.new-question-email', $question, $email ?: Lang::get('faq.no-email'));
        
        mail($siteEmail, $subject, $message, "From: $siteName <$siteEmail>\r\nContent-Type: text/html; charset=UTF-8");

        Show::msg(Lang::get('faq.question-sent'), 'success');
        Core::getInstance()->redirect($this->router->generate('faq-home'));
    }
}

