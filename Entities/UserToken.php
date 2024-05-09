<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToAlias;
use Modules\User\Entities\Sentinel\User;

class UserToken extends Model
{
    protected $table = 'user_tokens';
    protected $fillable = ['user_id', 'access_token'];

    /**
     * @return BelongsToAlias
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
