<?php

/**
 * Here you can register all of your application routes.
 *
 * Syntax GET: $router->get('uri','Controller@method');
 * Dynamic route: $router->method('uri/with/{dynamicvalue}','Controller@method');
 * Available router methods : get(), post(), put(), delete().
 */

// // WEB
// $router->get('', 'PagesHtmlController@index');
// $router->get('/', 'PagesHtmlController@index');
$router->get('/contact', 'PagesHtmlController@contact');
$router->get('/about', 'PagesHtmlController@about');
$router->get('/notification', 'PagesHtmlController@notification');
$router->get('/dashboard', 'PagesHtmlController@dashboard');
$router->get('/extra', 'PagesHtmlController@extra');

// SPA
$router->get('', 'PagesController@index');
$router->get('/', 'PagesController@index');
$router->post('/subscribe', 'PagesController@demoSpa');
$router->get('/demo/inventory', 'PagesController@demoSpa');
$router->get('/demo/prediction', 'PagesController@demoSpa');
$router->get('/demo/suppliers', 'PagesController@demoSpa');


// HTMX
$router->get('/login', 'DashboardController@login');
$router->post('/login/auth', 'DashboardController@loginAuth');

$router->get('/htmx', 'DashboardController@index');
$router->get('/htmx/dashboard', 'DashboardController@dashboard');
$router->get('/htmx/inventory', 'DashboardController@inventory');
$router->get('/htmx/assets', 'DashboardController@assets');
$router->get('/htmx/rental', 'DashboardController@rental');
$router->get('/htmx/rental-drone', 'DashboardController@rental2');

// HTMX-Dashboard Chart
$router->get('/data/data-dashboard/activities', 'DashboardController@data_dashboard_activities');
$router->get('/data/data-dashboard/stats', 'DashboardController@data_dashboard_activities');

// HTMX-Export
$router->get('/data/data-dashboard/export', 'DashboardController@data_dashboard_export');

// HTMX-CRUD Data
$router->get('/data/inventory-list', 'DashboardController@inventory_list');
$router->post('/data/save-product', 'DashboardController@save_product');
$router->get('/data/edit-product', 'DashboardController@edit_product');

// Inventory
$router->get('/data/get-products', 'DashboardController@inventory_list');
$router->post('/data/update-product', 'DashboardController@update_product');
$router->delete('/data/delete-product', 'DashboardController@delete_product');

// Assets
$router->get('/data/assets-render', 'DashboardController@assets_render');
$router->get('/data/asset-logs', 'DashboardController@assets_logs');
$router->get('/data/asset-add', 'DashboardController@assets_add');
$router->get('/data/asset-edit', 'DashboardController@assets_edit');
$router->post('/data/asset-store', 'DashboardController@assets_store');
$router->post('/data/asset-update', 'DashboardController@assets_update');

