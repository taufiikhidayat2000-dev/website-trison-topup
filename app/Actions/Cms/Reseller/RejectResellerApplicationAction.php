<?php

namespace App\Actions\Cms\Reseller;

use App\Models\Reseller\ResellerApplication;
use App\Models\User;

class RejectResellerApplicationAction
{
    /**
     * Handle the action.
     */
    public function handle(ResellerApplication $application, string $reason, User $admin): ResellerApplication
    {
        $application->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $application;
    }
}
