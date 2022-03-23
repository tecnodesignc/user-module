<?php

namespace Modules\User\Events;

use Cartalyst\Sentinel\Roles\RoleInterface;
use Modules\Core\Contracts\EntityIsChanging;
use Modules\Core\Events\AbstractEntityHook;

class RoleIsUpdating extends AbstractEntityHook implements EntityIsChanging
{
    /**
     * @var RoleInterface
     */
    private RoleInterface $role;

    public function __construct(RoleInterface $role, $attributes)
    {
        $this->role = $role;
        parent::__construct($attributes);
    }

    /**
     * @return RoleInterface
     */
    public function getRole(): RoleInterface
    {
        return $this->role;
    }
}
