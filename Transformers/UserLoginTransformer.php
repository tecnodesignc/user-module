<?php

namespace Modules\User\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Permissions\PermissionManager;
use Modules\User\Transformers\News\RoleTransformer;

class UserLoginTransformer extends JsonResource
{
    public function toArray($request)
    {
        $permissionsManager = app(PermissionManager::class);
        $permissions = $permissionsManager->buildPermissionList();
        $data = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->when($this->present()->fullname, $this->present()->fullname),
            'email' => $this->email,
            'is_activated' => $this->isActivated(),
            'main_image' => $this->present()->gravatar(),
            'last_login' => $this->last_login,
            'created_at' => $this->created_at,
            'permissions' => $permissions,
            'roles_id' => $this->roles->pluck('id'),
            'roles' => RoleTransformer::collection($this->roles),
            'urls' => [
                'logout' => route('api.user.logout'),
            ],
        ];
        return $data;
    }
}
