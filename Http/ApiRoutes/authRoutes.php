<?php

use Illuminate\Routing\Router;

/*=== AUTH ===*/
$router->group(['prefix' => '/auth'], function (Router $router) {

    /** @var Router $router */
  $router->post('reset', [
    'as' =>  'api.user.reset',
    'uses' => 'AuthApiController@reset',
  ]);
  /** @var Router $router */
  $router->post('reset-complete', [
    'as' =>  'api.user.reset-complete',
    'uses' => 'AuthApiController@resetComplete',
  ]);

  $router->post('register', [
    'as' =>  'api.auth.register',
    'uses' => 'UserApiController@register',
  ]);

  /** @var Router $router */
  $router->post('login', [
    'as' =>  'api.user.login',
    'uses' => 'AuthApiController@login',
  ]);

  /** @var Router $router */
  $router->get('logout', [
    'as' =>  'api.user.logout',
    'uses' => 'AuthApiController@logout',
  ]);

  /** @var Router $router */
  $router->get('logout-all', [
    'as' =>  'api.user.logout.all',
    'uses' => 'AuthApiController@logoutAllSessions',
  ]);

  /** @var Router $router */
  $router->get('must-change-password', [
    'as' =>  'api.user.me.must.change.password',
    'uses' => 'AuthApiController@mustChangePassword',
    'middleware' => ['user']
  ]);

  /** @var Router $router */
  $router->get('impersonate', [
    'as' =>  'api.profile.impersonate',
    'uses' => 'AuthApiController@impersonate',
  ]);

  /** @var Router $router */
  $router->get('refresh-token', [
    'as' =>  'api.user.refresh.token',
    'uses' => 'AuthApiController@refreshToken',
    'middleware' => ['user']
  ]);

  #==================================================== Social
  $router->post('social/{provider}', [
    'as' =>  'api.user.social.auth',
    'uses' => 'AuthApiController@getSocialAuth'
  ]);

  $router->get('social/callback/{provider}', [
    'as' =>   'api.user.social.callback',
    'uses' => 'AuthApiController@getSocialAuthCallback'
  ]);

});
