<?php

namespace Modules\User\Repositories\Sentinel;

use Cartalyst\Sentinel\Laravel\Facades\Activation;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\User\Entities\Sentinel\User;
use Modules\User\Events\UserHasRegistered;
use Modules\User\Events\UserIsCreating;
use Modules\User\Events\UserIsUpdating;
use Modules\User\Events\UserWasCreated;
use Modules\User\Events\UserWasUpdated;
use Modules\User\Exceptions\UserNotFoundException;
use Modules\User\Repositories\UserRepository;

class SentinelUserRepository implements UserRepository
{
    /**
     * @var \Modules\User\Entities\Sentinel\User
     */
    protected $user;
    /**
     * @var \Cartalyst\Sentinel\Roles\EloquentRole
     */
    protected $role;

    public function __construct()
    {
        $this->user = Sentinel::getUserRepository()->createModel();
        $this->role = Sentinel::getRoleRepository()->createModel();
    }

    /**
     * Returns all the users
     * @return object
     */
    public function all()
    {
        return $this->user->all();
    }

    /**
     * Create a user resource
     * @param  array $data
     * @param  bool $activated
     * @return mixed
     */
    public function create(array $data, $activated = false)
    {
        $this->hashPassword($data);

        event($event = new UserIsCreating($data));
        $user = $this->user->create($event->getAttributes());

        if ($activated) {
            $this->activateUser($user);
            event(new UserWasCreated($user));
        } else {
            event(new UserHasRegistered($user));
        }
        app(\Modules\User\Repositories\UserTokenRepository::class)->generateFor($user->id);

        return $user;
    }

    /**
     * Create a user and assign roles to it
     * @param  array $data
     * @param  array $roles
     * @param bool $activated
     * @return User
     */
    public function createWithRoles($data, $roles, $activated = false)
    {
        $user = $this->create((array) $data, $activated);

        if (!empty($roles)) {
            $user->roles()->attach($roles);
        }

        return $user;
    }

    /**
     * Create a user and assign roles to it
     * But don't fire the user created event
     * @param array $data
     * @param array $roles
     * @param bool $activated
     * @return User
     */
    public function createWithRolesFromCli($data, $roles, $activated = false)
    {
        $this->hashPassword($data);
        $user = $this->user->create((array) $data);

        if (!empty($roles)) {
            $user->roles()->attach($roles);
        }

        if ($activated) {
            $this->activateUser($user);
        }

        return $user;
    }

    /**
     * Find a user by its ID
     * @param $id
     * @return mixed
     */
    public function find($id)
    {
        return $this->user->find($id);
    }

    /**
     * Update a user
     * @param $user
     * @param $data
     * @return mixed
     */
    public function update($user, $data)
    {
        $this->checkForNewPassword($data);

        event($event = new UserIsUpdating($user, $data));

        $user->fill($event->getAttributes());
        $user->save();

        event(new UserWasUpdated($user));

        return $user;
    }

    /**
     * @param $userId
     * @param $data
     * @param $roles
     * @internal param $user
     * @return mixed
     */
    public function updateAndSyncRoles($userId, $data, $roles)
    {
        $user = $this->user->find($userId);

        $this->checkForNewPassword($data);

        $this->checkForManualActivation($user, $data);

        event($event = new UserIsUpdating($user, $data));

        $user->fill($event->getAttributes());
        $user->save();

        event(new UserWasUpdated($user));

        if (!empty($roles)) {
            $user->roles()->sync($roles);
        }
    }

    /**
     * Deletes a user
     * @param $id
     * @throws UserNotFoundException
     * @return mixed
     */
    public function delete($id)
    {
        if ($user = $this->user->find($id)) {
            return $user->delete();
        }

        throw new UserNotFoundException();
    }

    /**
     * Find a user by its credentials
     * @param  array $credentials
     * @return mixed
     */
    public function findByCredentials(array $credentials)
    {
        return Sentinel::findByCredentials($credentials);
    }

    /**
     * Paginating, ordering and searching through pages for server side index table
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function serverPaginationFilteringFor(Request $request): LengthAwarePaginator
    {
        $roles = $this->allWithBuilder();

        if ($request->get('search') !== null) {
            $term = $request->get('search');
            $roles->where('first_name', 'LIKE', "%{$term}%")
                ->orWhere('last_name', 'LIKE', "%{$term}%")
                ->orWhere('email', 'LIKE', "%{$term}%")
                ->orWhere('id', $term);
        }

        if ($request->get('order_by') !== null && $request->get('order') !== 'null') {
            $order = $request->get('order') === 'ascending' ? 'asc' : 'desc';

            $roles->orderBy($request->get('order_by'), $order);
        } else {
            $roles->orderBy('created_at', 'desc');
        }

        return $roles->paginate($request->get('per_page', 10));
    }

    public function allWithBuilder() : Builder
    {
        return $this->user->newQuery();
    }

    /**
     * Hash the password key
     * @param array $data
     */
    private function hashPassword(array &$data)
    {
        $data['password'] = Hash::make($data['password']);
    }

    /**
     * Check if there is a new password given
     * If not, unset the password field
     * @param array $data
     */
    private function checkForNewPassword(array &$data)
    {
        if (array_key_exists('password', $data) === false) {
            return;
        }

        if ($data['password'] === '' || $data['password'] === null) {
            unset($data['password']);

            return;
        }

        $data['password'] = Hash::make($data['password']);
    }

    /**
     * Check and manually activate or remove activation for the user
     * @param $user
     * @param array $data
     */
    private function checkForManualActivation($user, array &$data)
    {
        if (Activation::completed($user) && !$data['activated']) {
            return Activation::remove($user);
        }

        if (!Activation::completed($user) && $data['activated']) {
            $activation = Activation::create($user);

            return Activation::complete($user, $activation->code);
        }
    }

    /**
     * Activate a user automatically
     *
     * @param $user
     */
    private function activateUser($user)
    {
        $activation = Activation::create($user);
        Activation::complete($user, $activation->code);
    }

    public function getItemsBy($params)
    {
        /*== initialize query ==*/
        $query = $this->user->query();

        /*== RELATIONSHIPS ==*/
        if (in_array('*', $params->include)) {//If Request all relationships
            $query->with([]);
        } else {//specific relationships
            $includeDefault = [];//Default relationships
            if (isset($params->include))//merge relations with default relationships
                $includeDefault = array_merge($includeDefault, $params->include);
            $query->with($includeDefault);//Add Relationships to query
        }

        /*== FILTERS ==*/
        if (isset($params->filter)) {
            $filter = $params->filter;//Short filter
            //Filter by date
            if (isset($filter->date)) {
                $date = $filter->date;//Short filter date
                $date->field = $date->field ?? 'created_at';
                if (isset($date->from))//From a date
                    $query->whereDate($date->field, '>=', $date->from);
                if (isset($date->to))//to a date
                    $query->whereDate($date->field, '<=', $date->to);
            }

            //Order by
            if (isset($filter->order)) {
                $orderByField = $filter->order->field ?? 'created_at';//Default field
                $orderWay = $filter->order->way ?? 'desc';//Default way
                $query->orderBy($orderByField, $orderWay);//Add order to query
            } else {
                $query->orderByRaw('first_name ASC, last_name ASC');//Add order to query
            }
            //Filter by enables users
            if (isset($filter->status) && is_array($filter->status) && count($filter->status)) {
                $query->whereIn('users.id', function ($query) use ($filter) {
                    $query->select('activations.user_id')
                        ->from('activations')
                        ->where('activations.completed', $filter->status);
                });
            }

            //Filter by disabled users
            if (isset($filter->status) && ((int)$filter->status == 0)) {
                $query->whereNotIn('users.id', function ($query) use ($filter) {
                    $query->select('activations.user_id')
                        ->from('activations')
                        ->where('activations.completed', 1);
                });
            }

            //Filter by user ID
            if (isset($filter->userId) && count($filter->userId)) {
                $query->whereIn('users.id', $filter->userId);
            }


            //filter by Role ID
            if (isset($filter->roleId) && ((int)$filter->roleId) != 0) {
                $query->whereIn('id', function ($query) use ($filter) {
                    $query->select('user_id')->from('role_users')->where('role_id', $filter->roleId);
                });
            }

            //filter by Role Slug
            if (isset($filter->roleSlug)) {
                $query->whereIn('id', function ($query) use ($filter) {
                    $query->select('user_id')->from('role_users')->where('role_id', function ($subQuery) use ($filter) {
                        $subQuery->select('id')->from('roles')->where('slug', $filter->roleSlug);
                    });
                });
            }

            //filter by Roles
            if (isset($filter->roles) && count($filter->roles)) {
                $query->whereIn('id', function ($query) use ($filter) {
                    $query->select('user_id')->from('role_users')->whereIn('role_id', $filter->roles);
                });
            }

            //add filter by search
            if (isset($filter->search) && $filter->search) {
                $query->search($filter->search);
            }
            if (isset($filter->age)) {
                $age = $filter->age;//Short filter date
                if (isset($age->min) && isset($age->max)) {
                    $datemin = Carbon::now();
                    $datemax = Carbon::now();

                    if (isset($age->min))
                        $datemin->subYears($age->min);
                    if (isset($age->max))//to a date
                        $datemax->subYears($age->max + 1);
                    $query->whereDate('birthday', '<=', $datemin->toDateString());
                    $query->whereDate('birthday', '>=', $datemax->toDateString());
                }
            }

        }
        /*== FIELDS ==*/
        if (isset($params->fields) && count($params->fields))
            if (in_array('full_name', $params->fields)) {
                $params->fields = array_diff($params->fields, ['full_name']);
                $query->select($params->fields);
                $query->addSelect(\DB::raw('CONCAT(users.first_name,\' \',users.last_name) as full_name'));
            } else
                $query->select($params->fields);

        /*== REQUEST ==*/
        if (isset($params->page) && $params->page) {
            return $query->paginate($params->take);
        } else {
            $params->take ? $query->take($params->take) : false;//Take
            return $query->get();
        }
    }

    public function getItem($criteria, $params)
    {
        //Initialize query
        $query = $this->user->query();

        /*== RELATIONSHIPS ==*/
        if (isset($params->include) && in_array('*', $params->include)) {//If Request all relationships
            $query->with([]);
        } else {//Especific relationships
            $includeDefault = [];//Default relationships
            if (isset($params->include))//merge relations with default relationships
                $includeDefault = array_merge($includeDefault, $params->include);
            $query->with($includeDefault);//Add Relationships to query
        }

        /*== FILTER ==*/
        if (isset($params->filter)) {
            $filter = $params->filter;

            if (isset($filter->field))//Filter by specific field
                $field = $filter->field;
        }

        /*== FIELDS ==*/
        if (isset($params->fields) && count($params->fields)) {
            $params->fields = array_diff($params->fields, ['full_name']);
            $query->select($params->fields);
            $query->addSelect(\DB::raw('CONCAT(users.first_name,\' \',users.last_name) as full_name'));
        }

        /*== REQUEST ==*/
        return $query->where($field ?? 'id', $criteria)->first();
    }
}
