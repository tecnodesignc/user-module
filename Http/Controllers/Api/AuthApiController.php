<?php

namespace Modules\User\Http\Controllers\Api;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\User\Exceptions\InvalidOrExpiredResetCode;
use Modules\User\Exceptions\UserNotFoundException;
use Modules\User\Http\Requests\LoginRequest;
use Modules\User\Http\Requests\RegisterRequest;
use Modules\User\Http\Requests\ResetCompleteRequest;
use Modules\User\Http\Requests\ResetRequest;
use Modules\User\Services\UserRegistration;
use Modules\User\Services\UserResetter;
use Modules\User\Transformers\UserLoginTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\User\Repositories\UserRepository;
use Exception;

class AuthApiController extends BaseApiController
{
    use DispatchesJobs;

    private $user;

    public function __construct(UserRepository $user)
    {
        parent::__construct();
        $this->user = $user;
        $this->clearTokens();
    }

    public function login(LoginRequest $request)
    {
        try {
            $data = $request->all() ?? [];//Get data

            $user = auth()->attempt($data);
            if (!$user) {
                throw new \Exception(trans('user::messages.user or password invalid'), 401);
            }
            $token = $this->getToken($user);

            $response =  new UserLoginTransformer($user);
        } catch (\Exception $e) {
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);

    }

    /**
     * logout Api Controller
     * @param Request $request
     * @return mixed
     */
    public function logout(Request $request)
    {
        try {
           /* $token = $this->validateResponseApi($this->getRequestToken($request));//Get Token
            DB::table('oauth_access_tokens')->where('id', $token->id)->delete();//Delete Token*/
            $response = ["msg" => trans('user::messages.successfully logged out')];

        } catch (Exception $e) {
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    /**
     * logout All Sessions Api Controller
     * @param Request $request
     * @return mixed
     */
    public function logoutAllSessions(Request $request)
    {
        try {
            $data = $request->input('attributes') ?? [];//Get data

            if (isset($data['user_id'])) {
                //Delete all tokens of this user
                DB::table('oauth_access_tokens')->where('user_id', $data['user_id'])->delete();
            }else{
                throw new \Exception(trans('user::messages.user invalid'), 404);
            }
            $response = ["msg" => trans('user::messages.successfully logged out')];
        } catch (Exception $e) {
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }


    public function register(RegisterRequest $request)
    {
        try {
            $data = $request->all();//Get data
            app(UserRegistration::class)->register($data);
            $response = ["data" => ['msg' => trans('user::messages.account created check email for activation'), 'email' => $data['email']]];
        } catch (\Exception $e) {
            \Log::error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

//Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }


    public function reset(Request $request)
    {
        try {

            $data = $request->input('attributes') ?? [];//Get data
            $this->validateRequestApi(new ResetRequest($data));
            app(UserResetter::class)->startReset($data);

            $response = ['data' => [
                'msj' => trans('user::messages.check email to reset password')
            ]];
        } catch (UserNotFoundException $ex) {
            $status = $this->getStatusError($ex->getCode());
            $response = ["errors" => trans('user::messages.no user found')];
        } catch (Exception $e) {
            \Log::error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }


    public function resetComplete(Request $request)
    {
        try {
            $data = $request->input('attributes') ?? [];//Get data
            $this->validateRequestApi(new ResetCompleteRequest($data));
            app(UserResetter::class)->finishReset($data);

            $response = ["data" => ['msg' =>trans('user::messages.password reset')]];//Response

        } catch (UserNotFoundException $e) {
            \Log::error($e->getMessage());
            $status = $this->getStatusError(404);
            $response = ["errors" => trans('user::messages.no user found')];

        } catch (InvalidOrExpiredResetCode $e) {
            $status = $this->getStatusError(402);
            $response = ["errors" => trans('user::messages.invalid reset code')];
        } catch (Exception $e) {
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }


    /**
     * @param $request
     * @return mixed
     */
    private function getRequestToken($request)
    {
        try {
            $value = $request->bearerToken();//Get from request

            if ($value) {

                $tokenId = (new Parser(new JoseEncoder()))->parse($value)->claims()
                    ->all()['jti'];
                $token = \DB::table('oauth_access_tokens')->where('id', $tokenId)->first();//Find data Token

                $success = true;//Default state

                //Validate if exist token
                if (!isset($token)) $success = false;

                //Validate if is revoked
                if (isset($token) && (int)$token->revoked >= 1) $success = false;

                //Validate if Token expirated
                if (isset($token) && (strtotime(now()) >= strtotime($token->expires_at))) $success = false;

                //Revoke Token if is invalid
                if (!$success) {
                    if (isset($token)) $token->delete();//Delete token
                    throw new Exception('Unauthorized', 401);//Throw unautorize
                }

                $response = ['data' => $token];//Response Token ID decode
                DB::commit();//Commit to DataBase
            } else throw new Exception('Unauthorized', 401);//Throw unautorize
        } catch (Exception $e) {
            \Log::error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
    }


    /**
     * Provate method Clear Tokens
     */
    private function clearTokens()
    {
        //Delete all tokens expirateds or revoked
        DB::table('oauth_access_tokens')->where('expires_at', '<=', now())
            ->orWhere('revoked', 1)
            ->delete();
    }

    /**
     * @param $user
     * @return bool
     */
    private function getToken($user)
    {
        if (isset($user))
            return $user->createToken('Laravel Password Grant Client');
        else return false;
    }
}
