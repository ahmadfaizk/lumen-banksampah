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

$router->get('/key', function() {
    return \Illuminate\Support\Str::random(32);
});

$router->group(['prefix' => 'api/v1/'], function() use ($router) {
    $router->group(['prefix' => 'register'], function() use ($router) {
        $router->post('customer', 'AuthController@registerCustomer');
        $router->post('operator', 'AuthController@registerOperator');
        $router->post('card', 'OperatorController@registerCard');
    });
    $router->post('login', 'AuthController@login');

    $router->group(['prefix' => 'user'], function() use($router) {
        $router->get('/', 'UserController@index');
        $router->post('update', 'UserController@update');
        $router->post('changepassword', 'UserController@changePassword');
    });

    $router->group(['prefix' => 'customer'], function() use($router) {
        $router->get('/', 'CustomerController@index');
        $router->get('all', 'OperatorController@showCustomers');
        $router->get('search', 'OperatorController@searchCustomers');
        $router->get('{id}/delete', 'OperatorController@deleteCustomer');
        $router->get('unconfirmed', 'OperatorController@showCustomersUnconfirmed');
        $router->post('forgotpassword', 'AuthController@forgotPassswordCustomer');
        $router->post('withdraw', 'CustomerController@withdraw');
    });

    $router->group(['prefix' => 'complain'], function() use($router) {
        $router->get('/', 'OperatorController@showComplains');
        $router->post('add', 'CustomerController@complain');
    });

    $router->group(['prefix' => 'operator'], function() use($router) {
        $router->get('/', 'OperatorController@index');
        $router->post('forgotpassword', 'AuthController@forgotPasswordOperator');
    });

    $router->group(['prefix' => 'transaction'], function() use($router) {
        $router->post('deposit', 'OperatorController@deposit');
        $router->post('withdraw', 'OperatorController@withdraw');
        $router->post('deposit/card', 'OperatorController@depositCard');
        $router->group(['prefix' => 'history'], function() use($router) {
            $router->get('/', 'CustomerController@showHistory');
            $router->get('/{id}', 'OperatorController@showHistory');
        });
        $router->group(['prefix' => '{id}'], function() use($router) {
            $router->get('/', 'OperatorController@showTransaction');
            $router->post('edit', 'OperatorController@editTransaction');
            $router->get('delete', 'OperatorController@deleteTransaction');
        });
    });
});