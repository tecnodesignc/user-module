<?php

namespace Modules\User\Entities\Sentinel;

use Cartalyst\Sentinel\Laravel\Facades\Activation;
use Cartalyst\Sentinel\Users\EloquentUser;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laracasts\Presenter\PresentableTrait;
use Modules\Media\Support\Traits\MediaRelation;
use Modules\User\Entities\UserInterface;
use Modules\User\Entities\UserToken;
use Modules\User\Presenters\UserPresenter;
use Laravel\Passport\HasApiTokens;
use Modules\Media\Entities\File;

class User extends EloquentUser implements UserInterface, AuthenticatableContract
{
    use PresentableTrait, Authenticatable, HasApiTokens, MediaRelation;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'email',
        'password',
        'permissions',
        'first_name',
        'last_name',
        'fields'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'fields' => 'array'
    ];

    /**
     * {@inheritDoc}
     */
    protected $loginNames = ['email'];

    protected $presenter = UserPresenter::class;

    public function __construct(array $attributes = [])
    {
        $this->loginNames = config('encore.user.config.login-columns');
        $this->fillable = config('encore.user.config.fillable');
        if (config()->has('encore.user.config.presenter')) {
            $this->presenter = config('encore.user.config.presenter', UserPresenter::class);
        }
        if (config()->has('encore.user.config.casts')) {
            $this->casts = config('encore.user.config.casts', []);
        }

        parent::__construct($attributes);
    }

    /**
     * @inheritdoc
     */
    public function hasRoleId($roleId)
    {
        return $this->roles()->whereId($roleId)->count() >= 1;
    }

    /**
     * @inheritdoc
     */
    public function hasRoleSlug($slug)
    {
        return $this->roles()->whereSlug($slug)->count() >= 1;
    }

    /**
     * @inheritdoc
     */
    public function hasRoleName($name)
    {
        return $this->roles()->whereName($name)->count() >= 1;
    }

    /**
     * @inheritdoc
     */
    public function getFieldsAttribute($value)
    {
        return json_decode($value);
    }
    /**
     * @inheritdoc
     */
    public function isActivated()
    {
        if (Activation::completed($this)) {
            return true;
        }

        return false;
    }

    /**
     * @return HasMany
     */
    public function api_keys()
    {
        return $this->hasMany(UserToken::class);
    }
    /**
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->getKey()??0;
    }


    /**
     * @inheritdoc
     */
    public function getFirstApiKey()
    {
        $userToken = $this->api_keys->first();

        if ($userToken === null) {
            return '';
        }

        return $userToken->access_token;
    }

    public function getAvatarAttribute()
    {
        $thumbnail = $this->files()->where('zone', 'avatar')->first();
        if (!$thumbnail) {
            $image = [
                'mimeType' => 'image/jpeg',
                'path' => null
            ];
        } else {
            $image = [
                'mimeType' => $thumbnail->mimetype,
                'path' => $thumbnail->path_string
            ];
        }
        return json_decode(json_encode($image));

    }
    public function __call($method, $parameters)
    {
        #i: Convert array to dot notation
        $config = implode('.', ['encore.user.config.relations', $method]);

        #i: Relation method resolver
        if (config()->has($config)) {
            $function = config()->get($config);
            $bound = $function->bindTo($this);

            return $bound();
        }

        #i: No relation found, return the call to parent (Eloquent) to handle it.
        return parent::__call($method, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function hasAccess($permission)
    {
        $permissions = $this->getPermissionsInstance();

        return $permissions->hasAccess($permission);
    }
    public function toSearchableArray()
    {
        $array = [
            'id' => $this->id,
            'identification' => $this->identification_number,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone
        ];
        return $array;
    }

}
