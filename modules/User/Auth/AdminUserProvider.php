<?php

namespace Modules\User\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\User\Enums\UserType;
use Modules\User\Models\User;

class AdminUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        $user = parent::retrieveById($identifier);

        if ($user instanceof User && ! $user->isAdmin()) {
            return null;
        }

        return $user;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $user = parent::retrieveByToken($identifier, $token);

        if ($user instanceof User && ! $user->isAdmin()) {
            return null;
        }

        return $user;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $credentials['type'] = UserType::Admin->value;

        return parent::retrieveByCredentials($credentials);
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if ($user instanceof User && ! $user->isAdmin()) {
            return false;
        }

        return parent::validateCredentials($user, $credentials);
    }
}
