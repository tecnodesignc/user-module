<?php

namespace Modules\User\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\User\Contracts\Authentication;
use Modules\User\Entities\UserInterface;
use Modules\User\Repositories\UserTokenRepository;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Parser;

class TokenCan
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

    /**
     * @param Request $request
     * @param \Closure $next
     * @param string $permission
     * @return JsonResponse
     */
    public function handle(Request $request, \Closure $next, $permission):JsonResponse
    {
        if ($request->header('Authorization') === null) {
            return response()->json(["error" => trans('core::core.unauthenticated'), 'message' => trans('core::core.unauthenticated-access')], 401);
        }

        $user = $this->getUserFromToken($request->header('Authorization'));

        if ($user->hasAccess($permission) === false) {
            return response()->json(["error" => trans('core::core.unauthorized'), 'message' => trans('core::core.permission denied',['permission'=>$permission])], 401);
        }

        return $next($request);
    }

    /**
     * @param string $token
     * @return UserInterface
     */
    private function getUserFromToken($token):UserInterface
    {
        $token = $this->userToken->findByAttributes(['access_token' => $this->parseToken($token)]);

        return $token->user;
    }

    /**
     * @param string $token
     * @return string
     */
    private function parseToken($token):string
    {
        return str_replace('Bearer ', '', $token);
    }
}
