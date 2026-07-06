<?php

use CodeIgniter\Router\RouteCollection;

/**
 * IT Tickets routes.
 *
 * Temporary consolidation step: routes point directly to the remaining legacy controller
 * until the large controller can be fully renamed into the module controller safely.
 *
 * @var RouteCollection $routes
 */
$routes->group('it_tickets', [
    'namespace' => 'App\Controllers',
    'filter' => 'auth',
], static function (RouteCollection $routes): void {
    $routes->get('/', 'It_tickets::index');
    $routes->post('/', 'It_tickets::index');
    $routes->get('datatable', 'It_tickets::datatable');
    $routes->get('clearFilters', 'It_tickets::clearFilters');

    $routes->get('view/(:num)', 'It_tickets::view/$1');
    $routes->post('send', 'It_tickets::send');
    $routes->post('addComment/(:num)', 'It_tickets::addComment/$1');
    $routes->get('deleteComment/(:num)', 'It_tickets::deleteComment/$1');
    $routes->post('addAttachment/(:num)', 'It_tickets::addAttachment/$1');
    $routes->get('deleteAttachment/(:num)', 'It_tickets::deleteAttachment/$1');

    $routes->get('programming_plans', 'It_tickets::programming_plans');
    $routes->get('getProgrammingPlans', 'It_tickets::getProgrammingPlans');
    $routes->post('editProgrammingPlan', 'It_tickets::editProgrammingPlan');

    $routes->get('recurring', 'It_tickets::recurring');
    $routes->get('recurringDatatable', 'It_tickets::recurringDatatable');
    $routes->get('getRecurringTask/(:num)', 'It_tickets::getRecurringTask/$1');
    $routes->post('saveRecurringTask', 'It_tickets::saveRecurringTask');
    $routes->post('deleteRecurringTask/(:num)', 'It_tickets::deleteRecurringTask/$1');
    $routes->post('runRecurringTaskNow/(:num)', 'It_tickets::runRecurringTaskNow/$1');
    $routes->get('testRecurringTasks', 'It_tickets::testRecurringTasks');
});
