<?php

namespace Modules\User\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Cartalyst\Sentinel\Roles\EloquentRole  as Role;

class RoleTransformer extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'created_at' => $this->created_at,
            'urls' => [
                'delete_url' => route('api.user.role.destroy', $this->id),
            ],
        ];
    }
}
