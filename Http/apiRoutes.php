<?php

use Illuminate\Routing\Router;

$router->group(['prefix' => '/auth'], function (Router $router) {

    /** @var Router $router */
    $router->post('reset', [
        'as' => 'api.user.reset',
        'uses' => 'AuthApiController@reset',
    ]);
    /** @var Router $router */
    $router->post('reset-complete', [
        'as' => 'api.user.reset-complete',
        'uses' => 'AuthApiController@resetComplete',
    ]);

    $router->post('register', [
        'as' => 'api.auth.register',
        'uses' => 'AuthApiController@register',
    ]);

    /** @var Router $router */
    $router->post('login', [
        'as' => 'api.user.login',
        'uses' => 'AuthApiController@login',
    ]);

    /** @var Router $router */
    $router->get('logout', [
        'as' => 'api.user.logout',
        'uses' => 'AuthApiController@logout',
        'middleware' => ['api.token']
    ]);

    /** @var Router $router */
    $router->get('must-change-password', [
        'as' => 'api.user.me.must.change.password',
        'uses' => 'AuthApiController@mustChangePassword',
        'middleware' => ['api.token']
    ]);

    /** @var Router $router */
    $router->get('impersonate', [
        'as' => 'api.profile.impersonate',
        'uses' => 'AuthApiController@impersonate',
        'middleware' => ['api.token']
    ]);

    /** @var Router $router */
    $router->get('refresh-token', [
        'as' => 'api.user.refresh.token',
        'uses' => 'AuthApiController@refreshToken',
        'middleware' => ['api.token']
    ]);

    #==================================================== Social
    $router->post('social/{provider}', [
        'as' => 'api.user.social.auth',
        'uses' => 'AuthApiController@getSocialAuth'
    ]);

    $router->get('social/callback/{provider}', [
        'as' => 'api.user.social.callback',
        'uses' => 'AuthApiController@getSocialAuthCallback'
    ]);

});

/** @var Router $router */
$router->group(['prefix' => '/user', 'middleware' => ['api.token']], function (Router $router) {
    $router->group(['prefix' => 'roles'], function (Router $router) {
        $router->get('/', [
            'as' => 'api.user.role.index',
            'uses' => 'RoleController@index',
            'middleware' => 'token-can:user.roles.index',
        ]);
        $router->get('all', [
            'as' => 'api.user.role.all',
            'uses' => 'RoleController@all',
            'middleware' => 'token-can:user.roles.index',
        ]);
        $router->post('/', [
            'as' => 'api.user.role.store',
            'uses' => 'RoleController@store',
            'middleware' => 'token-can:user.roles.create',
        ]);
        $router->post('find/{role}', [
            'as' => 'api.user.role.find',
            'uses' => 'RoleController@find',
            'middleware' => 'token-can:user.roles.edit',
        ]);
        $router->post('find-new', [
            'as' => 'api.user.role.find-new',
            'uses' => 'RoleController@findNew',
            'middleware' => 'token-can:user.roles.edit',
        ]);
        $router->post('{role}/edit', [
            'as' => 'api.user.role.update',
            'uses' => 'RoleController@update',
            'middleware' => 'token-can:user.roles.edit',
        ]);
        $router->delete('{role}', [
            'as' => 'api.user.role.destroy',
            'uses' => 'RoleController@destroy',
            'middleware' => 'token-can:user.roles.destroy',
        ]);
    });

    $router->group(['prefix' => 'users'], function (Router $router) {
        $router->get('/', [
            'as' => 'api.user.user.index',
            'uses' => 'UserController@index',
            'middleware' => 'token-can:user.users.index',
        ]);
        $router->post('/', [
            'as' => 'api.user.user.store',
            'uses' => 'UserController@store',
            'middleware' => 'token-can:user.users.create',
        ]);
        $router->post('find/{user}', [
            'as' => 'api.user.user.find',
            'uses' => 'UserController@find',
            'middleware' => 'token-can:user.users.edit',
        ]);
        $router->post('find-new', [
            'as' => 'api.user.user.find-new',
            'uses' => 'UserController@findNew',
            'middleware' => 'token-can:user.users.edit',
        ]);
        $router->post('{user}/edit', [
            'as' => 'api.user.user.update',
            'uses' => 'UserController@update',
            'middleware' => 'token-can:user.users.edit',
        ]);
        $router->get('{user}/sendResetPassword', [
            'as' => 'api.user.user.sendResetPassword',
            'uses' => 'UserController@sendResetPassword',
            'middleware' => 'token-can:user.users.edit',
        ]);
        $router->delete('{user}', [
            'as' => 'api.user.user.destroy',
            'uses' => 'UserController@destroy',
            'middleware' => 'token-can:user.users.destroy',
        ]);
    });

    $router->group(['prefix' => '/account'], function (Router $router) {
        $router->get('profile', [
            'as' => 'api.account.profile.find-current-user',
            'uses' => 'ProfileController@findCurrentUser',
        ]);
        $router->post('profile', [
            'as' => 'api.account.profile.update',
            'uses' => 'ProfileController@update',
        ]);

        $router->get('api-keys', [
            'as' => 'api.account.api.index',
            'uses' => 'ApiKeysController@index',
            'middleware' => 'can:account.api-keys.index',
        ]);
        $router->get('api-keys/create', [
            'as' => 'api.account.api.create',
            'uses' => 'ApiKeysController@create',
            'middleware' => 'can:account.api-keys.create',
        ]);
        $router->delete('api-keys/{userTokenId}', [
            'as' => 'api.account.api.destroy',
            'uses' => 'ApiKeysController@destroy',
            'middleware' => 'can:account.api-keys.destroy',
        ]);
    });

    $router->get('permissions', [
        'as' => 'api.user.permissions.index',
        'uses' => 'PermissionsController@index',
        'middleware' => 'token-can:user.roles.index',
    ]);
});

