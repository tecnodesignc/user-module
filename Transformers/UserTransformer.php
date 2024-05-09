<?php

namespace Modules\User\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class UserTransformer extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->when($this->id,$this->id),
            'first_name' => $this->when($this->first_name,$this->first_name),
            'last_name' => $this->when($this->last_name,$this->last_name),
            'fullname' => $this->when($this->present()->fullname,$this->present()->fullname),
            'email' => $this->when($this->email,$this->email),
            'created_at' => $this->when($this->created_at,$this->created_at),
            'updated_at' => $this->when($this->created_at,$this->created_at),
            'last_login' => $this->last_login,
            'urls' => [
                'delete_url' => route('api.user.user.destroy', $this->id??0),
            ],
        ];
    }
}
