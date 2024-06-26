<?php

namespace Modules\User\Composers;

use Modules\User\Permissions\PermissionManager;

class PermissionsViewComposer
{
    /**
     * @var PermissionManager
     */
    private PermissionManager $permissions;

    public function __construct(PermissionManager $permissions)
    {
        $this->permissions = $permissions;
    }

    public function compose($view): void
    {
        // Get all permissions
        $view->permissions = $this->permissions->all();
    }
}
