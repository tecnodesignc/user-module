<?php

namespace Modules\User\Events;

use Modules\Media\Contracts\StoringMedia;

class UserWasCreated implements StoringMedia
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
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getEntity()
    {
        return $this->user;
    }

    /**
     * Return the ALL data sent
     * @return array
     */
    public function getSubmissionData()
    {
        return $this->data;
    }
}
