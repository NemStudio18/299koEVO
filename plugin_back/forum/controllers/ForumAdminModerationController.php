<?php

namespace Forum\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Core\Security\Csrf;

defined('ROOT') or exit('Access denied!');

class ForumAdminModerationController extends AdminController
{
    private $postManager;

    public function __construct()
    {
        parent::__construct();
        $this->postManager = new \Forum\Entities\PostManager();
    }

    public function list()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('forum', 'admin-moderate');
        
        $posts = \Forum\Entities\Post::query()
            ->where('is_approved', '=', 0)
            ->orderBy('created_at', 'DESC')
            ->get();
        
        $postsData = [];
        foreach ($posts as $post) {
            $data = $post->toArray();
            $topic = \Forum\Entities\Topic::findPK($post->topic_id);
            $data['topic_title'] = $topic ? (string)$topic->title : 'Sujet inconnu';
            $postsData[] = $data;
        }
        
        $tpl->set('posts', $postsData);
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('approveUrl', $this->router->generate('admin-forum-moderate-approve'));
        $tpl->set('deleteUrl', $this->router->generate('admin-forum-moderate-delete'));
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function approve()
    {
        $postId = (int)($this->request->post('post_id', 0));
        if (!Csrf::validate($this->request->post('_csrf', null), false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('admin-forum-moderate'));
        }
        if ($postId < 1) {
            Show::msg(Lang::get('forum.moderation-invalid'), 'error');
            return $this->core->redirect($this->router->generate('admin-forum-moderate'));
        }
        if ($this->postManager->approve($postId)) {
            Show::msg(Lang::get('forum.post-approved'), 'success');
        } else {
            Show::msg(Lang::get('forum.moderation-failed'), 'error');
        }
        return $this->core->redirect($this->router->generate('admin-forum-moderate'));
    }

    public function delete()
    {
        $postId = (int)($this->request->post('post_id', 0));
        if (!Csrf::validate($this->request->post('_csrf', null), false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('admin-forum-moderate'));
        }
        if ($postId < 1) {
            Show::msg(Lang::get('forum.moderation-invalid'), 'error');
            return $this->core->redirect($this->router->generate('admin-forum-moderate'));
        }
        if ($this->postManager->delete($postId)) {
            Show::msg(Lang::get('forum.post-deleted'), 'success');
        } else {
            Show::msg(Lang::get('forum.moderation-failed'), 'error');
        }
        return $this->core->redirect($this->router->generate('admin-forum-moderate'));
    }
}

