<?php

namespace Modules\User\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\History\Services\History;
use Modules\User\Contracts\Authentication;
use Modules\User\Entities\Sentinel\User;
use Modules\User\Events\UserHasBegunResetProcess;
use Modules\User\Http\Requests\CreateFieldsUserRequest;
use Modules\User\Http\Requests\CreateUserRequest;
use Modules\User\Http\Requests\RegisterRequest;
use Modules\User\Http\Requests\UpdateUserApiRequest;
use Modules\User\Http\Requests\UpdateUserRequest;
use Modules\User\Permissions\PermissionManager;
use Modules\User\Repositories\UserRepository;
use Modules\User\Transformers\News\FullUserTransformer;
use Modules\User\Transformers\News\UserTransformer;
use Modules\User\Services\UserRegistration;
use Cartalyst\Sentinel\Laravel\Facades\Activation;

class UserApiController extends BaseApiController
{
    /**
     * @var UserRepository
     */
    private $user;
    /**
     * @var PermissionManager
     */
    private $permissions;

    /**
     * @var History
     */
    private $serviceHistory;

    public function __construct(UserRepository $user, PermissionManager $permissions,  History $serviceHistory)
    {
        $this->user = $user;
        $this->permissions = $permissions;
    }
    /**
     * GET ITEMS
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            //Validate permissions
            $this->validatePermission($request, 'user.users.index');

            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //Request to Repository
            $users = $this->user->getItemsBy($params);

            //Response
            $response = ["data" => UserTransformer::collection($users)];

            //If request pagination add meta-page
            $params->page ? $response["meta"] = ["page" => $this->pageTransformer($users)] : false;
        } catch (\Exception $e) {
            \Log::Error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    /**
     * GET A ITEM
     *
     * @param $criteria
     * @return mixed
     */
    public function show($criteria, Request $request)
    {
        try {
            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //Request to Repository
            $user = $this->user->getItem($criteria, $params);

            //Break if no found item
            if (!$user) throw new \Exception('Item not found', 400);

            //Response
            //Response
            $response = ["data" => new FullUserTransformer($user)];

            //If request pagination add meta-page
            $params->page ? $response["meta"] = ["page" => $this->pageTransformer($user)] : false;
        } catch (\Exception $e) {
            \Log::error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
    }

    /**
     * Register users just default department and role
     *
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request)
    {
        try {
            //Validate permissions
            $this->validatePermission($request, 'user.user.create');
            $data = $request->input('attributes');//Get data from request

            $data['fields'] = $this->validateFields($data['fields']);
            $this->validateRequestApi(new CreateUserRequest($data));
            $this->validateRequestApi(new CreateFieldsUserRequest($data['fields']));
            $this->user->createWithRoles($data, $data['roles'] ?? null, true);
            $response = ["data" => ['msg' => trans('user::messages.user created')]];
        } catch (\Exception $e) {
            \Log::error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    /**
     * UPDATE ITEM
     *
     * @param $criteria
     * @param Request $request
     * @return mixed
     */
    public function update($criteria, Request $request)
    {
        \DB::beginTransaction(); //DB Transaction
        try {
            $data = $request->input('attributes');//Get data
            //Get Parameters from URL.

            //Validate permissions
            $params = $this->getParamsRequest($request);

            if ($params->user->id !== $criteria)
                $this->validatePermission($request, 'user.users.edit');


            $this->validateRequestApi(new UpdateUserApiRequest($data));
            $this->validateRequestApi(new CreateFieldsUserRequest($data['fields']));

            //Request to Repository
            $user = $this->user->getItem($criteria, $params);

            //Break if no found item
            if (!$user) throw new \Exception('Item not found', 400);


            //Validate Request
            $data['fields'] = $this->validateFields($data['fields']);

            \Log::info(json_encode($data['fields']));
            if (isset($data['permissions']))
                $data = $this->mergeRequestWithPermissions($data);
            if (isset($data['roles']))
                $this->user->updateAndSyncRoles($user->id, $data, $data['roles']);
            else
                $this->user->update($user, $data);

           // $this->serviceHistory->account('a')->to($this->user->id)->push('Actualizacion de Perfil',$this->user->present()->fullName(), null, null,2,$request->getClientIp());


            //Response
            $response = ["data" => ['msg' => trans('user::messages.user updated')]];

            \DB::commit();//Commit to DataBase
        } catch (\Exception $e) {
            \Log::error($e);
            \DB::rollback();//Rollback to Data Base
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
    }

    /**
     * UPDATE ITEM
     *
     * @param $criteria
     * @param Request $request
     * @return mixed
     */
    public function delete($criteria, Request $request)
    {
        \DB::beginTransaction(); //DB Transaction
        try {

            //Validate permissions
            $this->validatePermission($request, 'user.users.delete');
            $data = $request->input('attributes');//Get data
            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //Request to Repository
            $user = $this->user->getItem($criteria, $params);

            //Break if no found item
            if (!$user) throw new \Exception('Item not found', 400);

            $this->user->delete($user->id);

            //Response
            $response = ["data" => ['msg' => trans('user::messages.user deleted')]];

            \DB::commit();//Commit to DataBase
        } catch (\Exception $e) {
            \Log::error($e);
            \DB::rollback();//Rollback to Data Base
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
    }


    /**
     * Change password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        \DB::beginTransaction(); //DB Transaction
        try {
            //Auth api controller
            $authApiController = app('Modules\User\Http\Controllers\Api\AuthApiController');
            $requestLogout = new Request();

            //Get Parameters from URL.
            $params = $request->input('attributes');

            //Try to login and Get Token
            $token = $this->validateResponseApi($authApiController->authAttempt($params));
            $requestLogout->headers->set('Authorization', $token->bearer);//Add token to headers
            $user = Auth::user();//Get User

            //Check if password exist in history
            $usedPassword = $this->validateResponseApi($authApiController->checkPasswordHistory($params['newPassword']));

            //Update password
            $userUpdated = $this->validateResponseApi(
                $this->update($user->id, new request(
                    ['attributes' => [
                        'password' => $params['newPassword'],
                        'id' => $user->id,
                        'activated' => true
                    ]]
                ))
            );

            //Logout token
            $this->validateResponseApi($authApiController->logout($requestLogout));

            //response with userId
            $response = ['data' => ['userId' => $user->id]];
            \DB::commit();//Commit to DataBase
        } catch (\Exception $e) {
            \DB::rollback();//Rollback to Data Base
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Password updated"], $status ?? 200);

    }


    public function me(Request $request)
    {
        try {
            $user = Auth::user();
            $response = $this->transformData($request, $user, UserTransformer::class);
        } catch (\Exception $e) {
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
    }

    protected function mergeRequestWithPermissions($data)
    {
        $permissions = $this->permissions->clean($data['permissions']);
        return array_merge($data, ['permissions' => $permissions]);
    }

    protected function validateFields($fields = [])
    {
        $fieldsConfig = config('encore.user.config.fields');
        $data = [];
        foreach (array_keys($fieldsConfig) as $i => $key) {
            if (array_key_exists($key, $fields)) {
                $data[$key] = $fields[$key];
            }
        }
        return $data;
    }
}
