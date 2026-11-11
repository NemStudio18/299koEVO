<?php

defined('ROOT') OR exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Public routes
$router->map('GET', '/docs[/?]', 'Docs\\Controllers\\DocsListController#home', 'docs-home');
$router->map('GET', '/docs/cat-[*:name]-[i:id]/[i:page][/?]', 'Docs\\Controllers\\DocsListController#categoryPage', 'docs-category-page');
$router->map('GET', '/docs/cat-[*:name]-[i:id].html', 'Docs\\Controllers\\DocsListController#category', 'docs-category');
$router->map('GET', '/docs/[*:name]-[i:id].html', 'Docs\\Controllers\\DocsReadController#read', 'docs-read');
$router->map('GET', '/docs/[i:page][/?]', 'Docs\\Controllers\\DocsListController#page', 'docs-page');

// Admin routes (legacy controllers, to migrate)
$router->map('GET', '/admin/docs[/?]', 'Docs\\Controllers\\DocsAdminPagesController#list', 'admin-docs-list');
$router->map('POST', '/admin/docs/deletePage', 'Docs\\Controllers\\DocsAdminPagesController#deletePage', 'admin-docs-delete-page');
$router->map('GET', '/admin/docs/editPage/[i:id]?', 'Docs\\Controllers\\DocsAdminPagesController#editPage', 'admin-docs-edit-page');
$router->map('POST', '/admin/docs/savePage', 'Docs\\Controllers\\DocsAdminPagesController#savePage', 'admin-docs-save-page');
$router->map('POST', '/admin/docs/addCategory', 'Docs\\Controllers\\DocsAdminCategoriesController#addCategory', 'admin-docs-add-category');
$router->map('POST', '/admin/docs/deleteCategory', 'Docs\\Controllers\\DocsAdminCategoriesController#deleteCategory', 'admin-docs-delete-category');
$router->map('POST', '/admin/docs/editCategory', 'Docs\\Controllers\\DocsAdminCategoriesController#editCategory', 'admin-docs-edit-category');
$router->map('POST', '/admin/docs/saveCategory/[i:id]', 'Docs\\Controllers\\DocsAdminCategoriesController#saveCategory', 'admin-docs-save-category');
$router->map('GET', '/admin/docs/listAjaxCategories', 'Docs\\Controllers\\DocsAdminCategoriesController#listAjaxCategories', 'admin-docs-list-ajax-categories');
$router->map('GET', '/admin/docs/getCategoriesSelect', 'Docs\\Controllers\\DocsAdminCategoriesController#getCategoriesSelect', 'admin-docs-get-categories-select');
$router->map('GET', '/admin/docs/getCategoriesList', 'Docs\\Controllers\\DocsAdminCategoriesController#getCategoriesList', 'admin-docs-get-categories-list');
$router->map('POST', '/admin/docs/saveConfig', 'Docs\\Controllers\\DocsAdminConfigController#saveConfig', 'admin-docs-save-config');
$router->map('GET', '/admin/docs/history/[i:id]', 'Docs\\Controllers\\DocsAdminHistoryController#showHistory', 'admin-docs-history');
$router->map('GET', '/admin/docs/version/[i:id]/[i:version]', 'Docs\\Controllers\\DocsAdminHistoryController#showVersion', 'admin-docs-view-version');
$router->map('POST', '/admin/docs/restoreVersion', 'Docs\\Controllers\\DocsAdminHistoryController#restoreVersion', 'admin-docs-restore-version');
