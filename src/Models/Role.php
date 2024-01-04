<?php

namespace Sereny\NovaPermissions\Models;

use Sereny\NovaPermissions\Traits\SupportsRole;
use Spatie\Permission\Models\Role as SpatieRole;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Role extends SpatieRole
{
    use SupportsRole; // REQUIRED TRAIT
    use BelongsToTenant;
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::saving(function (Role $role) {
            if ($role->id === null) {
                $role->id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Force key type as string
     *
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }

    /**
     * Disable incrementing
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }
}