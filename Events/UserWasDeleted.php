<?php

namespace Modules\User\Events;

use Modules\Media\Contracts\DeletingMedia;

class UserWasDeleted implements DeletingMedia
{
     /**
     * @var int
     */
    private int $userId;

    /**
     * @var string
     */
    private string $userClass;

    public function __construct($userId, $userClass)
    {
        $this->userId = $userId;
        $this->userClass = $userClass;
    }

    /**
     * Get the entity ID
     * @return int
     */
    public function getEntityId():int
    {
        return $this->userId;
    }

    /**
     * Get the class name the imageables
     * @return string
     */
    public function getClassName():string
    {
        return $this->userClass;
    }
}
