<?php

use CodeIgniter\Router\RouteCollection;

/**
 * IT Tickets module routes.
 *
 * This file is intended to live under:
 * app/Modules/ItTickets/Config/Routes.php
 *
 * Add this to app/Config/Routes.php:
 *
 * if (file_exists(APPPATH . 'Modules/ItTickets/Config/Routes.php')) {
 *     require APPPATH . 'Modules/ItTickets/Config/Routes.php';
 * }
 *
 * @var RouteCollection $routes
 */
$routes->group('it_tickets', [
    'namespace' => 'App\Modules\ItTickets\Controllers',
    'filter' => 'auth',
], static function (RouteCollection $routes): void {
    $routes->get('/', 'ItTicketsController::index');
    $routes->post('/', 'ItTicketsController::index');
    $routes->get('datatable', 'ItTicketsController::datatable');
    $routes->get('clearFilters', 'ItTicketsController::clearFilters');

    $routes->get('view/(:num)', 'ItTicketsController::view/$1');
    $routes->post('send', 'ItTicketsController::send');
    $routes->post('addComment/(:num)', 'ItTicketsController::addComment/$1');
    $routes->get('deleteComment/(:num)', 'ItTicketsController::deleteComment/$1');
    $routes->post('addAttachment/(:num)', 'ItTicketsController::addAttachment/$1');
    $routes->get('deleteAttachment/(:num)', 'ItTicketsController::deleteAttachment/$1');

    $routes->get('programming_plans', 'ItTicketsController::programming_plans');
    $routes->get('getProgrammingPlans', 'ItTicketsController::getProgrammingPlans');
    $routes->post('editProgrammingPlan', 'ItTicketsController::editProgrammingPlan');

    $routes->get('recurring', 'ItTicketsController::recurring');
    $routes->get('recurringDatatable', 'ItTicketsController::recurringDatatable');
    $routes->get('getRecurringTask/(:num)', 'ItTicketsController::getRecurringTask/$1');
    $routes->post('saveRecurringTask', 'ItTicketsController::saveRecurringTask');
    $routes->post('deleteRecurringTask/(:num)', 'ItTicketsController::deleteRecurringTask/$1');
    $routes->post('runRecurringTaskNow/(:num)', 'ItTicketsController::runRecurringTaskNow/$1');
    $routes->get('testRecurringTasks', 'ItTicketsController::testRecurringTasks');
});
