<?php
namespace Sereny\NovaPermissions\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Illuminate\Validation\Rule;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\MorphToMany;
use Sereny\NovaPermissions\Fields\Checkboxes;
use Sereny\NovaPermissions\Models\Role as RoleModel;

class Role extends Resource
{

    /**
     * The list of field name that should be hidden
     *
     * @var string[]
     */
    public static $hiddenFields = [];

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = RoleModel::class;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name',
    ];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function fields(Request $request)
    {
        $guardOptions = $this->guardOptions($request);
        $userResource = $this->userResource();

        return [
            ID::make(__('ID'), 'id')
                ->rules('required')
                ->canSee(function ($request) {
                    return $this->fieldAvailable('id');
                }),

            Text::make(__('Name'), 'name')
                ->rules(['required', 'string', 'max:255']),

            Select::make(__('Guard Name'), 'guard_name')
                ->options($guardOptions->toArray())
                ->rules(['required', Rule::in($guardOptions)])
                ->canSee(function ($request) {
                    return $this->fieldAvailable('guard_name');
                })
                ->default($this->defaultGuard($guardOptions)),

            Checkboxes::make(__('Permissions'), 'permissions')
                ->options($this->loadPermissions()->map(function ($permission, $key) {
                    // Determine the label based on the permission name
                    $label = $permission->name;
                    if (preg_match('/^viewAny(.+)/', $label, $matches)) {
                        $label = 'View All ' . ucfirst($matches[1]);
                    } elseif (preg_match('/^view(.+)/', $label, $matches)) {
                        $label = 'Read ' . ucfirst($matches[1]);
                    } elseif (preg_match('/^ViewStats(.+)/', $label, $matches)) {
                        $label = 'Read Stats ' . ucfirst($matches[1]);
                    } elseif (preg_match('/^(update|create|delete|destroy)(.+)/', $label, $matches)) {
                        $label = ucfirst($matches[1]) . ' ' . ucfirst($matches[2]);
                    }

                    return [
                        'group'  => __(ucfirst($permission->group)),
                        'option' => $permission->name,
                        'label'  => __($label),
                    ];
                })
                    ->groupBy('group')
                    ->toArray()),


            Text::make(__('Users'), function () {
                return $this->users()->count();
            })->exceptOnForms(),

            MorphToMany::make($userResource::label(), 'users', $userResource)
                ->searchable()
                ->canSee(function ($request) {
                    return $this->fieldAvailable('users');
                }),
        ];
    }

    public static function label()
    {
        return __('Roles');
    }


    public static function singularLabel()
    {
        return __('Role');
    }

    /**
     * Load all permissions
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function loadPermissions()
    {
        /** @var class-string */
        $permissionClass = config('permission.models.permission');

        return $permissionClass::all()->unique('name');
    }
}
