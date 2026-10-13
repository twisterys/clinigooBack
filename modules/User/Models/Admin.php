<?php

namespace Modules\User\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Modules\User\Enums\UserType;

/**
 * Admin proxy model representing users with admin privileges.
 */
class Admin extends User
{
    protected $table = 'users';

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('admin', function (Builder $builder) {
            $builder->where('type', UserType::Admin->value);
        });

        static::creating(function (Admin $admin) {
            $admin->type = UserType::Admin;
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new()->admin();
    }
}
