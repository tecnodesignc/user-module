<?php

namespace Modules\User\Transformers\News;

use Illuminate\Http\Resources\Json\JsonResource;
use Cartalyst\Sentinel\Roles\EloquentRole  as Role;

class RoleTransformer extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->when($this->id,$this->id),
            'name' => $this->when($this->name,$this->name),
            'slug' => $this->when($this->slug,$this->slug),
            'created_at' => $this->when($this->created_at,$this->created_at),
            'users'=>UserTransformer::collection($this->whenLoaded('users')),
            'urls' => [
                'delete_url' => route('api.user.role.destroy', $this->id),
            ],
        ];
    }
}
