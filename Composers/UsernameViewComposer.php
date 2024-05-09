<?php

namespace Modules\User\Composers;

use Illuminate\Contracts\View\View;
use Modules\User\Contracts\Authentication;

class UsernameViewComposer
{
    /**
     * @var Authentication
     */
    private Authentication $auth;

    public function __construct(Authentication $auth)
    {
        $this->auth = $auth;
    }

    /**
     * @param View $view
     * @return void
     */
    public function compose(View $view): void
    {
        $view->with('user', $this->auth->user());
    }
}
