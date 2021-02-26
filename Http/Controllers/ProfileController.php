<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\Response;
use Modules\Core\Http\Controllers\BasePublicController;
use Modules\User\Http\Requests\UpdateProfileRequest;
use Modules\User\Repositories\UserRepository;

class ProfileController extends BasePublicController
{
    /**
     * @var UserRepository
     */
    private $user;

    public function __construct(UserRepository $user)
    {
        parent::__construct();
        $this->user = $user;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function show()
    {
        return view('user::public.account.profile.show');
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit()
    {
        return view('user::public.account.profile.edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     * @param  UpdateProfileRequest $request
     *
     * @return Response
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $this->auth->user();

        $this->user->update($user, $request->all());

        return redirect()->back()
            ->withSuccess(trans('user::messages.profile updated'));
    }
}
