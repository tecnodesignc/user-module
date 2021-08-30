<?php

use Illuminate\Routing\Router;

$router->group(['prefix' => '/roles', 'middleware' => ['auth:api']], function (Router $router) {
    $locale = \LaravelLocalization::setLocale() ?: \App::getLocale();

    $router->post('/', [
        'as' => 'api.user.roles.create',
        'uses' => 'RoleApiController@create',
    ]);
    $router->get('/', [
        'as' => 'api.user.roles.index',
        'uses' => 'RoleApiController@index',
    ]);
    $router->put('/{criteria}', [
        'as' => 'api.user.roles.update',
        'uses' => 'RoleApiController@update',
    ]);
    $router->delete('/{criteria}', [
        'as' => 'api.user.roles.delete',
        'uses' => 'RoleApiController@delete',
    ]);
    $router->get('/{criteria}', [
        'as' => 'api.user.roles.show',
        'uses' => 'RoleApiController@show',
    ]);

});
