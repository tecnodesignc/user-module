<?php

namespace Modules\User\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiKeysTransformer extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'access_token' => $this->access_token,
            'created_at' => $this->created_at,
        ];
    }
}
