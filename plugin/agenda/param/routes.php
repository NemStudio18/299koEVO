<?php

defined('ROOT') OR exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Admin routes
$router->map('GET', '/admin/agenda[/?]', 'Agenda\\Controllers\\AgendaAdminController#home', 'admin-agenda-home');
$router->map('GET', '/admin/agenda/events[/?]', 'Agenda\\Controllers\\AgendaAdminController#list', 'admin-agenda-events');
$router->map('GET', '/admin/agenda/events/add', 'Agenda\\Controllers\\AgendaAdminController#add', 'admin-agenda-events-add');
$router->map('POST', '/admin/agenda/events/add', 'Agenda\\Controllers\\AgendaAdminController#addSend', 'admin-agenda-events-add-send');
$router->map('GET', '/admin/agenda/events/edit/[*:id]', 'Agenda\\Controllers\\AgendaAdminController#edit', 'admin-agenda-events-edit');
$router->map('POST', '/admin/agenda/events/edit/[*:id]', 'Agenda\\Controllers\\AgendaAdminController#editSend', 'admin-agenda-events-edit-send');
$router->map('GET', '/admin/agenda/events/delete/[*:id]', 'Agenda\\Controllers\\AgendaAdminController#delete', 'admin-agenda-events-delete');
$router->map('GET', '/admin/agenda/events/approve/[*:id]', 'Agenda\\Controllers\\AgendaAdminController#approve', 'admin-agenda-events-approve');
$router->map('POST', '/admin/agenda/save-config', 'Agenda\\Controllers\\AgendaAdminController#saveConfig', 'admin-agenda-save-config');

// Public routes
$router->map('GET', '/agenda[/?]', 'Agenda\\Controllers\\AgendaController#index', 'agenda-index');
$router->map('GET', '/agenda/page/[i:page]', 'Agenda\\Controllers\\AgendaController#index', 'agenda-index-page');
$router->map('GET', '/agenda/api/events', 'Agenda\\Controllers\\AgendaController#apiEvents', 'agenda-api-events');
$router->map('GET', '/agenda/export.ics[/?]', 'Agenda\\Controllers\\AgendaController#exportAllIcal', 'agenda-export-all-ical');
$router->map('GET', '/agenda/event/add', 'Agenda\\Controllers\\AgendaController#add', 'agenda-event-add');
$router->map('POST', '/agenda/event/add', 'Agenda\\Controllers\\AgendaController#addSend', 'agenda-event-add-send');
$router->map('GET', '/agenda/event/propose', 'Agenda\\Controllers\\AgendaController#propose', 'agenda-event-propose');
$router->map('POST', '/agenda/event/propose', 'Agenda\\Controllers\\AgendaController#proposeSend', 'agenda-event-propose-send');
$router->map('GET', '/agenda/event/[*:id]/ical[/?]', 'Agenda\\Controllers\\AgendaController#exportIcal', 'agenda-event-ical');
$router->map('POST', '/agenda/event/[*:id]/attendance', 'Agenda\\Controllers\\AgendaController#toggleAttendance', 'agenda-event-attendance-toggle');
$router->map('GET', '/agenda/event/[*:id]', 'Agenda\\Controllers\\AgendaController#event', 'agenda-event');
