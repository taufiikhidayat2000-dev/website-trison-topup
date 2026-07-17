<?php

namespace App\Actions\Main;

use App\Models\Reseller\ResellerApplication;
use App\Models\User;

class SubmitResellerApplicationAction
{
    /**
     * Handle the action.
     */
    public function handle(User $user, array $data): ResellerApplication
    {
        return $user->resellerApplications()->create([
            'business_name' => $data['business_name'],
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);
    }
}
