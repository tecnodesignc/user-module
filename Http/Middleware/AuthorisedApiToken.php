<?php

namespace Modules\User\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\User\Contracts\Authentication;
use Modules\User\Repositories\UserTokenRepository;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Parser;

class AuthorisedApiToken
{
    /**
     * @var UserTokenRepository
     */
    private $userToken;
    /**
     * @var Authentication
     */
    private $auth;

    /**
     * @var passportToken
     */
    private $passportToken;

    public function __construct(UserTokenRepository $userToken, Authentication $auth, TokenRepository $passportToken)
    {
        $this->userToken = $userToken;
        $this->auth = $auth;
        $this->passportToken = $passportToken;
    }

    public function handle(Request $request, \Closure $next)
    {
        if ($request->header('Authorization') === null) {
            return response()->json(["error" => trans('core::core.unauthenticated'), 'message' => trans('core::core.unauthenticated-access')], 401);
        }

        if ($this->isValidToken($request->header('Authorization')) === false) {
            return response()->json(["error" => trans('core::core.unauthorized'), 'message' => trans('core::core.unauthorized-access')], 401);
        }

        return $next($request);
    }

    private function isValidToken($token)
    {
        $found = $this->userToken->findByAttributes(['access_token' => $this->parseToken($token)]);

        if ($found === null) {
            $user = auth('api')->user();//$this->passportToken->find($id);
            if ($user === null)
                return false;
        } else
            $user = $found->user;
        if (!$user->isActivated()) return false;
        $this->auth->logUserIn($user);
        if (!$this->auth->hasAccess('account.api-keys.index')) {
            return false;
        }
        return true;
    }

    private function parseToken($token)
    {
        return str_replace('Bearer ', '', $token);
    }
}
