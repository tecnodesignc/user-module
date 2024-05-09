<?php

namespace Modules\User\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Permissions\PermissionManager;
use Modules\User\Transformers\RoleTransformer;

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
            'full_name' => $this->present()->fullname,
            'fields'=>$this->fields,
            'email' => $this->email,
            'avatar' => $this->present()->gravatar(),
            'roles_id' => $this->roles->pluck('id'),
            'is_activated' => $this->isActivated(),
            'api_token'=>$this->getFirstApiKey(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'last_login' => $this->last_login,
            'roles' => RoleTransformer::collection($this->roles),
            'permissions' => $permissions,
            'urls' => [
                'logout' => route('api.user.logout'),
            ],
        ];
        return $data;
    }
}
