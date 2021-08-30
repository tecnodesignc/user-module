<?php

use Illuminate\Routing\Router;

$router->group(['prefix' => '/users'], function (Router $router) {
    $locale = \LaravelLocalization::setLocale() ?: \App::getLocale();

    $router->post('/create', [
        'as' => 'api.user.users.create',
        'uses' => 'UserApiController@create',
        'middleware' => ['auth:api']
    ]);
    $router->get('/', [
        'as' => 'api.user.users.index',
        'uses' => 'UserApiController@index',
        'middleware' => ['auth:api']
    ]);
    $router->put('change-password', [
        'as' => 'api.user.change.password',
        'uses' => 'UserApiController@changePassword',
        'middleware' => ['auth:api']
    ]);
    $router->get('me', [
        'as' => 'api.user.users.me',
        'uses' => 'UserApiController@me',
        'middleware' => ['auth:api']
    ]);
    $router->put('/{criteria}', [
        'as' => 'api.user.users.update',
        'uses' => 'UserApiController@update',
        'middleware' => ['auth:api']
    ]);
    $router->delete('/{criteria}', [
        'as' => 'api.user.users.delete',
        'uses' => 'UserApiController@delete',
        'middleware' => ['auth:api']
    ]);
    $router->get('/{criteria}', [
        'as' => 'api.user.users.show',
        'uses' => 'UserApiController@show',
        'middleware' => ['auth:api']
    ]);
});
