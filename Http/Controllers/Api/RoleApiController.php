<?php

namespace Modules\User\Http\Controllers\Api;

use Cartalyst\Sentinel\Roles\EloquentRole;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\User\Http\Requests\CreateRoleRequest;
use Modules\User\Http\Requests\UpdateRoleApiRequest;
use Modules\User\Permissions\PermissionManager;
use Modules\User\Repositories\RoleRepository;
use Modules\User\Transformers\News\FullRoleTransformer;
use Modules\User\Transformers\News\RoleTransformer;

class RoleApiController extends BaseApiController
{
    /**
     * @var RoleRepository
     */
    private $role;
    /**
     * @var PermissionManager
     */
    private $permissions;

    public function __construct(RoleRepository $role, PermissionManager $permissions)
    {
        $this->role = $role;
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
            $this->validatePermission($request, 'user.roles.index');
            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //Request to Repository
            $roles = $this->role->getItemsBy($params);

            //Response
            $response = [
                "data" => RoleTransformer::collection($roles)
            ];

            //If request pagination add meta-page
            $params->page ? $response["meta"] = ["page" => $this->pageTransformer($roles)] : false;
        } catch (\Exception $e) {
            \Log::error($e);
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
            $role = $this->role->getItem($criteria, $params);

            //Break if no found item
            if (!$role) throw new \Exception('Item not found', 404);

            //Response
            $response = ["data" => new FullRoleTransformer($role)];

            //If request pagination add meta-page
            $params->page ? $response["meta"] = ["page" => $this->pageTransformer($role)] : false;
        } catch (\Exception $e) {
            \Log::error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
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
            //Validate Permission
            $this->validatePermission($request, 'profile.role.create');

            //Get data
            $data = $request->input('attributes');

            //Validate Request
            $this->validateRequestApi(new CreateRoleRequest((array)$data));
            $data = $this->mergeRequestWithPermissions($data);
            //Create item
            $role = $this->role->create($data);

            //Response
            $response = ["data" => ['msg'=>trans('user::messages.role created')]];
            \DB::commit(); //Commit to Data Base
        } catch (\Exception $e) {
            \DB::rollback();//Rollback to Data Base
            \Log::error($e);
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
            //Validate Permission
            $this->validatePermission($request, 'user.roles.edit');
            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //Request to Repository
            $role = $this->role->getItem($criteria, $params);

            //Break if no found item
            if (!$role) throw new \Exception('Item not found', 404);

            //Get data
            $data = $request->input('attributes');

            //Validate Request
            $this->validateRequestApi(new UpdateRoleApiRequest($data));

            $data = $this->mergeRequestWithPermissions($data);

            $this->role->update($role->id, $data);

            $response = ["data" => ['msg'=>trans('user::messages.role updated')]];
            \DB::commit();//Commit to DataBase
        } catch (\Exception $e) {
            \DB::rollback();//Rollback to Data Base
            \Log::error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
    }


    /**
     * DELETE A ITEM
     *
     * @param $criteria
     * @return mixed
     */
    public function delete($criteria, Request $request)
    {
        \DB::beginTransaction();
        try {
            //Validate Permission
            $this->validatePermission($request, 'profile.role.destroy');

            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //Request to Repository
            $role = $this->role->getItem($criteria, $params);

            //Break if no found item
            if (!$role) throw new \Exception('Item not found', 404);

            //call Method delete
            $this->role->delete($role->id);

            //Response
            $response = ["data" => ['msg'=>trans('user::messages.role deleted')]];
            \DB::commit();//Commit to Data Base
        } catch (\Exception $e) {
            \DB::rollback();//Rollback to Data Base
            \Log::error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
    }

    /**
     * @param  $data
     * @return array
     */
    protected function mergeRequestWithPermissions($data)
    {
        $permissions = $this->permissions->clean($data['permissions']);
        return array_merge($data, ['permissions' => $permissions]);
    }
}
