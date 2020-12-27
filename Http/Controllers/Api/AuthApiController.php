<?php

namespace Modules\User\Http\Controllers\Api;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\User\Exceptions\InvalidOrExpiredResetCode;
use Modules\User\Exceptions\UserNotFoundException;
use Modules\User\Http\Requests\LoginRequest;
use Modules\User\Http\Requests\RegisterRequest;
use Modules\User\Http\Requests\ResetCompleteRequest;
use Modules\User\Http\Requests\ResetRequest;
use Modules\User\Services\UserRegistration;
use Modules\User\Services\UserResetter;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class AuthApiController extends BaseApiController
{
    use DispatchesJobs;

    public function __construct()
    {
        parent::__construct();
    }


    public function login(Request $request)
    {
        try {
            $credentials = [ //Get credentials
                'email' => $request->input('email'),
                'password' => $request->input('password')
            ];
            $this->validateRequestApi(new LoginRequest($credentials));
            $token = auth()->attempt($credentials);
            if (!$token) {
                throw new \Exception('User or Password invalid', 401);
            } else {
                return $this->respondWithToken($token);
            }
        } catch (\Exception $e) {
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);

    }
    public function reset(ResetRequest $request)
    {
        try {

            app(UserResetter::class)->startReset($request->all());

            $response = ['data' => [
                'msj' => trans('user::messages.check email to reset password')
            ]];

        } catch (UserNotFoundException $ex) {
            $status = $this->getStatusError($ex->getCode());
            $response = ["errors" => trans('user::messages.no user found')];
        } catch (\Exception $e) {
            \Log::error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }
    }

    public function resetComplete($userId, $code, ResetCompleteRequest $request)
    {
        try {
            app(UserResetter::class)->finishReset(
                array_merge($request->all(), ['userId' => $userId, 'code' => $code])
            );
        } catch (UserNotFoundException $e) {
            return redirect()->back()->withInput()
                ->withError(trans('user::messages.user no longer exists'));
        } catch (InvalidOrExpiredResetCode $e) {
            return redirect()->back()->withInput()
                ->withError(trans('user::messages.invalid reset code'));
        }

        return redirect()->route('login')
            ->withSuccess(trans('user::messages.password reset'));
    }


    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 36000 * 60 * 60
        ]);
    }

}
