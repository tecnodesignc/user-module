<?php

namespace Modules\User\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\User\Contracts\Authentication;
use Modules\User\Entities\Sentinel\User;
use Modules\User\Events\UserHasBegunResetProcess;
use Modules\User\Http\Requests\CreateUserRequest;
use Modules\User\Http\Requests\RegisterRequest;
use Modules\User\Http\Requests\UpdateUserRequest;
use Modules\User\Permissions\PermissionManager;
use Modules\User\Repositories\UserRepository;
use Modules\User\Transformers\UserTransformer;
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

    public function __construct(UserRepository $user, PermissionManager $permissions)
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
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
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
            $response = ["data" => new UserTransformer($user)];

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
    public function register(Request $request)
    {
        try {
            $data = (object)$request->input('attributes');//Get data from request
            $filter = [];//define filters
            $validateEmail = config()->get('encore.user.validate');
            //Validate custom Request user
            $this->validateRequestApi(new CreateUserRequest((array)$data));

            //Format dat ot create user
            $params = [
                'attributes' => [
                    'first_name' => $data->first_name,
                    'last_name' => $data->last_name,
                    'fields' => $data->fields,
                    'email' => $data->email,
                    'password' => $data->password,
                    'password_confirmation' => $data->password_confirmation,
                    'activated' => (int)$validateEmail ? false : true
                ],
                'filter' => json_encode([
                    'checkEmail' => (int)$validateEmail ? 1 : 0
                ])
            ];


            if (isset($data->roles) && !in_array(1, $data->roles)) {
                $params['attributes']['roles'] =  $data->roles;
            } else {
                $params['attributes']['roles'] =  [2];
            }

            //Create user
            $user = $this->validateResponseApi($this->create(new Request($params)));

            //Response and especific if user required check email
            $response = ["data" => ['checkEmail' => (int)$validateEmail ? true : false]];
        } catch (\Exception $e) {
            \Log::error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    /**
     * CREATE A ITEM
     *
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request)
    {
        \DB::beginTransaction();
        try {
            //Validate permissions
            $this->validatePermission($request, 'user.user.create');

            //Get data
            $data = $request->input('attributes');
            $data["email"] = strtolower($data["email"]);//Parse email to lowercase
            $params = $this->getParamsRequest($request);
            $checkEmail = isset($params->filter->checkEmail) ? $params->filter->checkEmail : false;

            $this->validateRequestApi(new RegisterRequest($data));//Validate Request User
            $this->validateRequestApi(new CreateUserRequest($data));//Validate custom Request user

            if ($checkEmail) //Create user required validate email
                $user = app(UserRegistration::class)->register($data);
            else //Create user activated
                $user =  $this->user->createWithRoles($data, $data["roles"], $data["activated"]);

            $response = ["data" => "User Created"];
            \DB::commit(); //Commit to Data Base
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
    public function update($criteria, Request $request)
    {
        \DB::beginTransaction(); //DB Transaction
        try {
            //Validate permissions
            $this->validatePermission($request, 'profile.user.edit');
            $data = $request->input('attributes');//Get data
            $params = $this->getParamsRequest($request);//Get Params

            //Validate Request
            $this->validateRequestApi(new UpdateUserRequest((array)$data));

            if (isset($data["email"])) {
                $data["email"] = strtolower($data["email"]);
                $user = $this->user->findByAttributes(["email" => $data["email"]]);
            }
            if (!isset($user) || !$user || ($user->id == $data["id"])) {
                $user = $this->user->findByAttributes(["id" => $data["id"]]);
                $oldData = $user->toArray();

                // configuting activate data to audit
                if (Activation::completed($user) && !$data['activated'])
                    $oldData['activated'] = 1;
                if (!Activation::completed($user) && $data['activated'])
                    $oldData['activated'] = 0;

                // actually user roles
                $userRolesIds = $user->roles()->get()->pluck('id')->toArray();
                $this->user->updateAndSyncRoles($data["id"], $data, []);
                $user = $this->user->findByAttributes(["id" => $data["id"]]);

                // saving old passrond
                if (isset($data["password"]))
                    $oldData["password"] = $user->password;

                if (isset($data["roles"])) {
                    // check roles to Attach and Detach
                    $rolesToAttach = array_diff(array_values($data['roles']), $userRolesIds);
                    $rolesToDetach = array_diff($userRolesIds, array_values($data['roles']));

                    // sync roles
                    if (!empty($rolesToAttach)) {
                        $user->roles()->attach($rolesToAttach);
                    }
                    if (!empty($rolesToDetach)) {
                        $user->roles()->detach($rolesToDetach);
                    }
                }
                // configuring pasword to audit
                if (isset($data["password"]))
                    $data["password"] = $user->password;

                //Response
                $response = ["data" => $user];
            } else {
                $status = 400;
                $response = ["errors" => $data["email"] . ' | User Name already exist'];
            }
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

    /**
     * Upload media files
     *
     * @param Request $request
     * @return mixed
     */
    public function mediaUpload(Request $request)
    {
        try {
            $auth = \Auth::user();
            $data = $request->all();//Get data
            $user_id = $data['user'];
            $name = $data['nameFile'];
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $nameFile = $name . '.' . $extension;
            $allowedextensions = array('JPG', 'JPEG', 'PNG', 'GIF', 'ICO', 'BMP', 'PDF', 'DOC', 'DOCX', 'ODT', 'MP3', '3G2', '3GP', 'AVI', 'FLV', 'H264', 'M4V', 'MKV', 'MOV', 'MP4', 'MPG', 'MPEG', 'WMV');
            $destination_path = 'assets/user/profile/files/' . $user_id . '/' . $nameFile;
            $disk = 'publicmedia';
            if (!in_array(strtoupper($extension), $allowedextensions)) {
                throw new Exception(trans('iprofile::profile.messages.file not allowed'));
            }
            if ($user_id == $auth->id || $auth->hasAccess('user.users.create')) {

                if (in_array(strtoupper($extension), ['JPG', 'JPEG'])) {
                    $image = \Image::make($file);

                    \Storage::disk($disk)->put($destination_path, $image->stream($extension, '90'));
                } else {

                    \Storage::disk($disk)->put($destination_path, \File::get($file));
                }

                $status = 200;
                $response = ["data" => ['url' => $destination_path]];


            } else {
                $status = 403;
                $response = [
                    'error' => [
                        'code' => '403',
                        "title" => trans('user::user.messages.access denied'),
                    ]
                ];
            }

        } catch (\Exception $e) {
            \Log::Error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    /**
     * delete media
     *
     * @param Request $request
     * @return mixed
     */
    public function mediaDelete(Request $request)
    {
        try {
            $disk = "publicmedia";
            $auth = \Auth::user();
            $data = $request->all();//Get data
            $user_id = $data['user'];
            $dirdata = $request->input('file');

            if ($user_id == $auth->id || $auth->hasAccess('user.users.create')) {

                \Storage::disk($disk)->delete($dirdata);

                $status = 200;
                $response = [
                    'susses' => [
                        'code' => '201',
                        "source" => [
                            "pointer" => url($request->path())
                        ],
                        "title" => trans('core::core.messages.resource delete'),
                        "detail" => [
                        ]
                    ]
                ];
            } else {
                $status = 403;
                $response = [
                    'error' => [
                        'code' => '403',
                        "title" => trans('user::user.messages.access denied'),
                    ]
                ];
            }

        } catch (\Exception $e) {
            \Log::Error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    public function me(Request $request){
        try{
            $user=Auth::user();
            $response = $this->transformData($request, $user, UserTransformer::class);
        }catch (\Exception $e){
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
    }
}
