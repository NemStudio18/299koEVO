<?php
defined("ROOT") or exit("No direct script access allowed");

$router = \Core\Router\Router::getInstance();

// Flux public / citoyen
$router->map("GET", "/localfeed[/?]", "LocalFeed\\Controllers\\LocalFeedController#page", "localfeed-page");
$router->map("GET", "/mur[/?]", "LocalFeed\\Controllers\\LocalFeedController#page", "localfeed-page-mur");
$router->map("GET", "/localfeed/feed", "LocalFeed\\Controllers\\LocalFeedController#feed", "localfeed-feed");
$router->map("GET", "/localfeed/poi/[i:id]", "LocalFeed\\Controllers\\LocalFeedController#poiFeed", "localfeed-poi-feed");

// Interactions citoyennes
$router->map("POST", "/localfeed/react", "LocalFeed\\Controllers\\LocalFeedController#react", "localfeed-react");
$router->map("POST", "/localfeed/react/[a:type]/[i:id]", "LocalFeed\\Controllers\\LocalFeedController#reactItem", "localfeed-react-item");
$router->map("POST", "/localfeed/post/[i:id]/like", "LocalFeed\\Controllers\\LocalFeedController#toggleLike", "localfeed-post-like");
$router->map("POST", "/localfeed/post/[i:id]/share", "LocalFeed\\Controllers\\LocalFeedController#recordShare", "localfeed-post-share");
$router->map("GET", "/localfeed/post/[i:id]/comments", "LocalFeed\\Controllers\\LocalFeedController#getComments", "localfeed-post-comments");
$router->map("POST", "/localfeed/post/[i:id]/comment", "LocalFeed\\Controllers\\LocalFeedController#addComment", "localfeed-post-comment-add");
$router->map("POST|DELETE", "/localfeed/comment/[i:id]/delete", "LocalFeed\\Controllers\\LocalFeedController#deleteComment", "localfeed-comment-delete");

// Gestion par le commerçant / pro
$router->map("POST", "/localfeed/poi/[i:id]/publish", "LocalFeed\\Controllers\\LocalFeedController#publishPost", "localfeed-poi-publish");
$router->map("POST|DELETE", "/localfeed/post/[i:id]/delete", "LocalFeed\\Controllers\\LocalFeedController#deletePost", "localfeed-post-delete");
