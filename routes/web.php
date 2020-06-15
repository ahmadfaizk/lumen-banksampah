<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api/v1/'], function() use ($router) {
    $router->post('register/customer', 'AuthController@registerCustomer');
    $router->post('register/operator', 'AuthController@registerOperator');
    $router->post('login', 'AuthController@login');

    $router->group(['prefix' => 'user'], function() use($router) {
        $router->get('/', 'UserController@index');
        $router->post('update', 'UserController@update');
    });

    $router->group(['prefix' => 'customer'], function() use($router) {
        $router->get('/', 'CustomerController@index');
        $router->get('all', 'OperatorController@showCustomers');
        $router->get('search', 'OperatorController@searchCustomers');
        $router->get('unconfirmed', 'OperatorController@showCustomersUnconfirmed');
        $router->get('history', 'CustomerController@history');
    });

    $router->group(['prefix' => 'complain'], function() use($router) {
        $router->get('/', 'OperatorController@showComplains');
        $router->post('add', 'CustomerController@complain');
    });

    $router->group(['prefix' => 'operator'], function() use($router) {
        $router->get('/', 'OperatorController@index');
    });

    $router->group(['prefix' => 'transaction'], function() use($router) {
        $router->get('history', 'CustomerController@showHistory');
        $router->get('{id}', 'OperatorController@showHistory');
        $router->post('deposit', 'OperatorController@deposit');
        $router->post('withdraw', 'OperatorController@withdraw');
    });
});