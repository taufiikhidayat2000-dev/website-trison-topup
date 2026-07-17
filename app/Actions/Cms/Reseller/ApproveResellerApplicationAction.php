<?php

namespace App\Actions\Cms\Reseller;

use App\Models\Reseller\ResellerApplication;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApproveResellerApplicationAction
{
    /**
     * Handle the action.
     */
    public function handle(ResellerApplication $application, User $admin): ResellerApplication
    {
        return DB::transaction(function () use ($application, $admin) {
            $application->update([
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $application->user->assignRole('reseller');

            // Bust the shared Inertia auth cache so the user's role/pricing
            // updates without needing to log out and back in.
            Cache::forget('auth:user:'.$application->user_id);

            return $application;
        });
    }
}
