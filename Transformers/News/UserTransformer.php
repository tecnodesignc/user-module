<?php

namespace Modules\User\Transformers\News;

use Illuminate\Http\Resources\Json\JsonResource;

class UserTransformer extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->when($this->id,$this->id),
            'first_name' => $this->when($this->first_name,$this->first_name),
            'last_name' => $this->when($this->last_name,$this->last_name),
            'full_name' => $this->when($this->present()->fullname,$this->present()->fullname),
            'email' => $this->when($this->email,$this->email),
            'is_activated' => $this->isActivated(),
            'roles_id' => $this->roles->pluck('id'),
            'roles' => RoleTransformer::collection($this->roles),
            'created_at' => $this->when($this->created_at,$this->created_at),
            'updated_at' => $this->when($this->updated_at,$this->updated_at),
            'last_login' => $this->when($this->last_login,$this->last_login),
            'urls' => [
                'delete_url' => route('api.user.user.destroy', $this->id??0),
            ],
        ];
    }
}
