<?php

namespace Modules\User\Presenters;

use Laracasts\Presenter\Presenter;
use Modules\Media\Entities\File;

class UserPresenter extends Presenter
{
    /**
     * Return the gravatar link for the users email
     * @param  int $size
     * @return string
     */
    public function gravatar($size = 90)
    {

        if (!isset($this->mainimage->path)) {
            $email = md5($this->email);
            $image="https://www.gravatar.com/avatar/$email?s=$size";
        } else {
            $image=$this->mainimage->path;
        }
        return $image;
    }

    /**
     * @return string
     */
    public function fullname()
    {
        return $this->name ?: $this->first_name . ' ' . $this->last_name;
    }
}
