<?php

namespace Modules\User\Http\Controllers\Api;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\User\Permissions\PermissionManager;
use Modules\User\Permissions\PermissionsRemover;

class PermissionsApiController extends BaseApiController
{
    /**
     * @var PermissionManager
     */
    private $permissionManager;

    public function __construct(PermissionManager $permissionManager)
    {
        $this->permissionManager = $permissionManager;
    }

    public function index()
    {
        $permissions=$this->permissionManager->all();
        foreach ($permissions as $p=>$permission){
            foreach ($permission as $i=>$actions){
              foreach ($actions as $a=>$name){
                  $permissions[$p][$i][$a]=trans($name);
              }

            }
        }

        return response()->json([
            'permissions' => $permissions,
        ]);
    }
}
