<?php

namespace Modules\User\Events;

use Illuminate\Database\Eloquent\Model;
use Modules\Media\Contracts\StoringMedia;

class UserWasUpdated implements StoringMedia
{

    /**
     * @var array
     */
    public $data;

    public $user;

    public function __construct($user, array $data)
    {
        $this->user = $user;
        $this->data = $data;
    }
    /**
     * Return the entity
     * @return Model
     */
    public function getEntity(): Model
    {
        return $this->user;
    }

    /**
     * Return the ALL data sent
     * @return array
     */
    public function getSubmissionData(): array
    {
        return $this->data;
    }
}
