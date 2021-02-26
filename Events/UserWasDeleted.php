<?php

namespace Modules\User\Events;

use Modules\Media\Contracts\DeletingMedia;

class UserWasDeleted implements DeletingMedia
{
    /**
     * @var string
     */
    private $userClass;
    /**
     * @var int
     */
    private $userId;

    public function __construct($userId, $userClass)
    {
        $this->userClass = $userClass;
        $this->userId = $userId;
    }

    /**
     * Get the entity ID
     * @return int
     */
    public function getEntityId()
    {
        return $this->userId;
    }

    /**
     * Get the class name the imageables
     * @return string
     */
    public function getClassName()
    {
        return $this->userClass;
    }
}
