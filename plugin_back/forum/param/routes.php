<?php

defined('ROOT') OR exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Admin routes
$router->map('GET', '/admin/forum[/?]', 'Forum\\Controllers\\ForumAdminController#home', 'admin-forum-home');
$router->map('GET', '/admin/forum/categories[/?]', 'Forum\\Controllers\\ForumAdminCategoriesController#list', 'admin-forum-categories');
$router->map('GET', '/admin/forum/categories/add', 'Forum\\Controllers\\ForumAdminCategoriesController#add', 'admin-forum-categories-add');
$router->map('POST', '/admin/forum/categories/add', 'Forum\\Controllers\\ForumAdminCategoriesController#addSend', 'admin-forum-categories-add-send');
$router->map('GET', '/admin/forum/categories/edit/[i:id]', 'Forum\\Controllers\\ForumAdminCategoriesController#edit', 'admin-forum-categories-edit');
$router->map('POST', '/admin/forum/categories/edit/[i:id]', 'Forum\\Controllers\\ForumAdminCategoriesController#editSend', 'admin-forum-categories-edit-send');
$router->map('POST', '/admin/forum/categories/delete/[i:id]', 'Forum\\Controllers\\ForumAdminCategoriesController#delete', 'admin-forum-categories-delete');
$router->map('GET', '/admin/forum/moderate[/?]', 'Forum\\Controllers\\ForumAdminModerationController#list', 'admin-forum-moderate');
$router->map('POST', '/admin/forum/moderate/approve', 'Forum\\Controllers\\ForumAdminModerationController#approve', 'admin-forum-moderate-approve');
$router->map('POST', '/admin/forum/moderate/delete', 'Forum\\Controllers\\ForumAdminModerationController#delete', 'admin-forum-moderate-delete');
$router->map('POST', '/admin/forum/config/save', 'Forum\\Controllers\\ForumAdminController#saveConfig', 'admin-forum-config-save');
$router->map('GET', '/admin/forum/maintenance', 'Forum\\Controllers\\ForumAdminCategoriesController#maintenance', 'admin-forum-maintenance');
$router->map('POST', '/admin/forum/maintenance/run', 'Forum\\Controllers\\ForumAdminCategoriesController#runMaintenance', 'admin-forum-maintenance-run');

// Public routes
$router->map('GET', '/forum[/?]', 'Forum\\Controllers\\ForumController#index', 'forum-index');
$router->map('GET', '/forum/category/[i:id]', 'Forum\\Controllers\\ForumController#category', 'forum-category');
$router->map('GET', '/forum/topic/[i:id]', 'Forum\\Controllers\\ForumController#topic', 'forum-topic');
$router->map('GET', '/forum/topic/[i:id]/reply', 'Forum\\Controllers\\ForumController#showReply', 'forum-topic-reply');
$router->map('GET', '/forum/topic/create', 'Forum\\Controllers\\ForumController#showCreateTopic', 'forum-topic-create-form');
$router->map('POST', '/forum/topic/create', 'Forum\\Controllers\\ForumController#createTopic', 'forum-topic-create');
$router->map('POST', '/forum/topic/lock/[i:id]', 'Forum\\Controllers\\ForumController#toggleLock', 'forum-topic-lock');
$router->map('POST', '/forum/topic/pin/[i:id]', 'Forum\\Controllers\\ForumController#togglePin', 'forum-topic-pin');
$router->map('POST', '/forum/post/create', 'Forum\\Controllers\\ForumController#createPost', 'forum-post-create');
$router->map('POST', '/forum/post/approve', 'Forum\\Controllers\\ForumController#approvePost', 'forum-post-approve');
$router->map('POST', '/forum/post/delete', 'Forum\\Controllers\\ForumController#deletePostModeration', 'forum-post-delete-moderation');
$router->map('POST', '/forum/topic/moderate/delete', 'Forum\\Controllers\\ForumController#deleteTopicModeration', 'forum-topic-moderate-delete');
$router->map('POST', '/forum/topic/delete', 'Forum\\Controllers\\ForumController#deleteTopic', 'forum-topic-delete');
$router->map('POST', '/forum/post/delete', 'Forum\\Controllers\\ForumController#deletePost', 'forum-post-delete');
$router->map('GET', '/forum/post/edit/[i:id]', 'Forum\\Controllers\\ForumController#editPost', 'forum-post-edit');
$router->map('POST', '/forum/post/edit/[i:id]', 'Forum\\Controllers\\ForumController#editPostSend', 'forum-post-edit-send');
$router->map('POST', '/forum/post/like/[i:id]', 'Forum\\Controllers\\ForumController#toggleLike', 'forum-post-like');
$router->map('GET', '/forum/search', 'Forum\\Controllers\\ForumController#search', 'forum-search');
$router->map('GET', '/forum/notifications', 'Forum\\Controllers\\ForumController#notifications', 'forum-notifications');
$router->map('POST', '/forum/notifications/read/[i:id]', 'Forum\\Controllers\\ForumController#markNotificationRead', 'forum-notification-read');

