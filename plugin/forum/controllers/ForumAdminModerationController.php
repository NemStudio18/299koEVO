<?php

namespace Forum\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Lang;
use Core\Security\Csrf;
use Utils\Show;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class ForumAdminModerationController extends AdminController
{
    private $postManager;
    private $topicManager;

    public function __construct()
    {
        parent::__construct();
        $this->postManager = new \Forum\Entities\PostManager();
        $this->topicManager = new \Forum\Entities\TopicManager();
    }

    private function canModerate(): bool
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        return $evaluator->canAccess(new \Core\Navigation\GenericResource('forum:moderate'), $ctx)
            || $evaluator->canAccess(new \Core\Navigation\GenericResource('forum:admin'), $ctx);
    }

    public function list()
    {
        if (!$this->canModerate()) {
            Show::msg(Lang::get('forum.moderation-not-allowed'), 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('forum', 'admin-moderate');

        $pendingPosts = [];
        foreach ($this->postManager->getPending() as $post) {
            $data = $post->toArray();
            $topic = $this->topicManager->getById($post->getTopicId());
            $data['topic_title'] = $topic ? $topic->getTitle() : '';
            $data['topic_url'] = $topic ? $topic->getUrl() : '#';
            $data['created_at_formatted'] = Util::FormatDate($post->getCreatedAt(), 'fr', 'fr');
            $pendingPosts[] = $data;
        }

        $tpl->set('posts', $pendingPosts);
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('approveUrl', $this->router->generate('admin-forum-moderate-approve'));
        $tpl->set('deleteUrl', $this->router->generate('admin-forum-moderate-delete'));
        $tpl->set('backUrl', $this->router->generate('admin-forum-home'));
        $response->addTemplate($tpl);
        return $response;
    }

    public function approve()
    {
        $csrf = $_POST['_csrf'] ?? $_POST['token'] ?? null;
        if (!$this->canModerate() || !Csrf::validate($csrf, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('admin-forum-moderate'));
        }

        $postId = (int) ($_POST['post_id'] ?? 0);
        if ($postId === 0) {
            Show::msg(Lang::get('forum.moderation-invalid'), 'error');
            return $this->core->redirect($this->router->generate('admin-forum-moderate'));
        }

        if ($this->postManager->approve($postId)) {
            Show::msg(Lang::get('forum.post-approved'), 'success');
        } else {
            Show::msg(Lang::get('forum.moderation-failed'), 'error');
        }

        $redirect = !empty($_POST['redirect']) ? (string) $_POST['redirect'] : $this->router->generate('admin-forum-moderate');
        return $this->core->redirect($redirect);
    }

    public function delete()
    {
        $csrf = $_POST['_csrf'] ?? $_POST['token'] ?? null;
        if (!$this->canModerate() || !Csrf::validate($csrf, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('admin-forum-moderate'));
        }

        $postId = (int) ($_POST['post_id'] ?? 0);
        if ($postId === 0) {
            Show::msg(Lang::get('forum.moderation-invalid'), 'error');
            return $this->core->redirect($this->router->generate('admin-forum-moderate'));
        }

        if ($this->postManager->delete($postId)) {
            Show::msg(Lang::get('forum.post-deleted'), 'success');
        } else {
            Show::msg(Lang::get('forum.moderation-failed'), 'error');
        }

        $redirect = !empty($_POST['redirect']) ? (string) $_POST['redirect'] : $this->router->generate('admin-forum-moderate');
        return $this->core->redirect($redirect);
    }
}
