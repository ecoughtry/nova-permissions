<?php

namespace Sereny\NovaPermissions\Nova;

use Sereny\NovaPermissions\Traits\ModelForGuardResolver;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource as NovaResource;
use Illuminate\Support\Facades\Auth;


/**
 * @property static string[] $hiddenFields
 */
abstract class RoleResource extends NovaResource
{
    use ModelForGuardResolver;

    /**
     * The callback that should be used to resolve guards that can be used.
     *
     * @var \Closure|null
     */
    public static $resolveGuardsCallback;

    /**
     * Get the available guards.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return string
     */
    protected function guards($request)
    {
        if (static::$resolveGuardsCallback) {
            return call_user_func(static::$resolveGuardsCallback, $request);
        }

        return array_keys(config('auth.guards'));
    }

    /**
     * When is available only one guard the default value is the first element.
     *
     * @param \Illuminate\Support\Collection  $options
     * @return string
     */
    protected function defaultGuard($options)
    {
        return $options->count() === 1 ? $options->first() : null;
    }

    /**
     * Get mapped guard options.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return \Illuminate\Support\Collection
     */
    protected function guardOptions($request)
    {
        return collect($this->guards($request))->mapWithKeys(function ($value) {
            return [$value => __($value)];
        });
    }

    /**
     * Determines the user resource
     *
     * @return bool
     */
    protected function userResource()
    {
        $model = $this->modelForGuard();

        return Nova::resourceForModel($model);
    }

    /**
     * Determines if the field is available
     *
     * @param string $name
     * @return bool
     */
    protected function fieldAvailable($name)
    {
        return ! in_array($name, static::$hiddenFields);
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is authenticated and filter the query based on tenant_id
        if ($user && isset($user->tenant_id)) {
            $query->where('tenant_id', $user->tenant_id);
        }

        // Additional condition to hide "super-admin" roles for non "@ticketsnap.ca" email domains
        if ($user && !Str::endsWith($user->email, '@ticketsnap.ca')) {
            $query->where('name', '!=', 'super-admin');
        }

        return $query;
    }

}
