<?php

namespace Modules\User\Repositories\Sentinel;

use Cartalyst\Sentinel\Checkpoints\NotActivatedException;
use Cartalyst\Sentinel\Checkpoints\ThrottlingException;
use Cartalyst\Sentinel\Laravel\Facades\Activation;
use Cartalyst\Sentinel\Laravel\Facades\Reminder;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Cartalyst\Sentinel\Users\UserInterface;
use Modules\User\Contracts\Authentication;
use Modules\User\Entities\UserInterface as UserModelInterface;
use Modules\User\Events\UserHasActivatedAccount;

class SentinelAuthentication implements Authentication
{
    /**
     * Authenticate a user
     * @param  array $credentials
     * @param  bool  $remember    Remember the user
     * @return mixed
     */
    public function login(array $credentials, $remember = false): mixed
    {
        try {
            if (Sentinel::authenticate($credentials, $remember)) {
                return false;
            }

            return trans('user::users.invalid login or password');
        } catch (NotActivatedException $e) {
            return trans('user::users.account not validated');
        } catch (ThrottlingException $e) {
            $delay = $e->getDelay();

            return trans('user::users.account is blocked', ['delay' => $delay]);
        }
    }

    /**
     * Register a new user.
     * @param  array $user
     * @return bool|UserInterface
     */
    public function register(array $user): bool|UserInterface
    {
        return Sentinel::getUserRepository()->create((array) $user);
    }

    /**
     * Assign a role to the given user.
     * @param  \Modules\User\Repositories\UserRepository $user
     * @param  \Modules\User\Repositories\RoleRepository $role
     * @return mixed
     */
    public function assignRole($user, $role): mixed
    {
        return $role->users()->attach($user);
    }

    /**
     * Log the user out of the application.
     * @return bool
     */
    public function logout(): mixed
    {
        return Sentinel::logout();
    }

    /**
     * Activate the given used id
     * @param  int    $userId
     * @param  string $code
     * @return mixed
     */
    public function activate($userId, $code): mixed
    {
        $user = Sentinel::findById($userId);

        $success = Activation::complete($user, $code);
        if ($success) {
            event(new UserHasActivatedAccount($user));
        }

        return $success;
    }

    /**
     * Create an activation code for the given user
     * @param  \Modules\User\Repositories\UserRepository $user
     * @return mixed
     */
    public function createActivation($user): mixed
    {
        return Activation::create($user)->code;
    }

    /**
     * Create a reminders code for the given user
     * @param  \Modules\User\Repositories\UserRepository $user
     * @return mixed
     */
    public function createReminderCode($user): mixed
    {
        $reminder = Reminder::exists($user) ?: Reminder::create($user);

        return $reminder->code;
    }

    /**
     * Completes the reset password process
     * @param $user
     * @param  string $code
     * @param  string $password
     * @return bool
     */
    public function completeResetPassword($user, $code, $password): bool
    {
        return Reminder::complete($user, $code, $password);
    }

    /**
     * Determines if the current user has access to given permission
     * @param $permission
     * @return bool
     */
    public function hasAccess($permission) : bool
    {
        if (! Sentinel::check()) {
            return false;
        }

        return Sentinel::hasAccess($permission);
    }

    /**
     * Check if the user is logged in
     * @return bool
     */
    public function check() : bool
    {
        $user = Sentinel::check();

        if ($user) {
            return true;
        }

        return false;
    }

    /**
     * Get the currently logged in user
     * @return UserModelInterface|bool
     */
    public function user(): UserModelInterface|bool
    {
        return Sentinel::check();
    }

    /**
     * Get the ID for the currently authenticated user
     * @return int
     */
    public function id() : int
    {
        $user = $this->user();

        if ($user === false) {
            return 0;
        }

        return $user->id;
    }

    public function logUserIn(UserInterface $user) : UserInterface
    {
        return Sentinel::login($user);
    }
}
