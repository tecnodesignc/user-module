<?php

namespace Modules\User\Transformers\News;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Permissions\PermissionManager;

class FullUserTransformer extends JsonResource
{
    public function toArray($request)
    {
        $permissionsManager = app(PermissionManager::class);
        $permissions =$permissionsManager->buildPermissionList();

        $data = [
            'id' => $this->id,
            'first_name' => $this->when($this->first_name,$this->first_name),
            'last_name' => $this->when($this->last_name,$this->last_name),
            'full_name' => $this->when($this->present()->fullname,$this->present()->fullname),
            'email' => $this->when($this->email,$this->email),
            'fields'=>$this->fields,
            'main_image'=>$this->present()->gravatar(),
            'is_activated' => $this->isActivated(),
            'created_at' => $this->when($this->created_at,$this->created_at),
            'updated_at' => $this->when($this->updated_at,$this->updated_at),
            'last_login' => $this->when($this->last_login,$this->last_login),
            'permissions' => $permissions,
            'roles_id' => $this->when($this->roles,$this->roles->pluck('id')),
            'roles' => RoleTransformer::collection($this->whenLoaded('roles')),
            'urls' => [
                'delete_url' => route('api.user.user.destroy', $this->id??0),
            ],
        ];

        return $data;
    }
}
