<?php

namespace Modules\User\Contracts;

use Cartalyst\Sentinel\Users\UserInterface;
use \Modules\User\Entities\UserInterface as UserModelInterface;
use Modules\User\Repositories\RoleRepository;
use Modules\User\Repositories\UserRepository;

interface Authentication
{
    /**
     * Authenticate a user
     * @param  array $credentials
     * @param  bool  $remember    Remember the user
     * @return mixed
     */
    public function login(array $credentials, $remember = false): mixed;

    /**
     * Register a new user.
     * @param array $user
     * @return bool|UserInterface
     */
    public function register(array $user): bool|UserInterface;

    /**
     * Activate the given used id
     * @param  int    $userId
     * @param  string $code
     * @return mixed
     */
    public function activate($userId, $code): mixed;

    /**
     * Assign a role to the given user.
     * @param  UserRepository $user
     * @param  RoleRepository $role
     * @return mixed
     */
    public function assignRole($user, $role): mixed;

    /**
     * Log the user out of the application.
     * @return mixed
     */
    public function logout(): mixed;

    /**
     * Create an activation code for the given user
     * @param $user
     * @return mixed
     */
    public function createActivation($user): mixed;

    /**
     * Create a reminders code for the given user
     * @param $user
     * @return mixed
     */
    public function createReminderCode($user): mixed;

    /**
     * Completes the reset password process
     * @param $user
     * @param  string $code
     * @param  string $password
     * @return bool
     */
    public function completeResetPassword($user, $code, $password): bool;

    /**
     * Determines if the current user has access to given permission
     * @param $permission
     * @return bool
     */
    public function hasAccess($permission): bool;

    /**
     * Check if the user is logged in
     * @return bool
     */
    public function check(): bool;

    /**
     * Get the currently logged in user
     * @return UserModelInterface|bool
     */
    public function user(): UserModelInterface|bool;

    /**
     * Get the ID for the currently authenticated user
     * @return int
     */
    public function id();

    /**
     * Log a user manually in
     * @param UserInterface $user
     * @return UserInterface
     */
    public function logUserIn(UserInterface $user) : UserInterface;
}
