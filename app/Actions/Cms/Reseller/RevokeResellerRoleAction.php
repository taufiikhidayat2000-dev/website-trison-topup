<?php

namespace App\Actions\Cms\Reseller;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RevokeResellerRoleAction
{
    /**
     * Handle the action.
     */
    public function handle(User $user): User
    {
        $user->removeRole('reseller');

        Cache::forget('auth:user:'.$user->id);

        return $user;
    }
}
