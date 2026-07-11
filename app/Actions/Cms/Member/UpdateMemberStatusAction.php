<?php

namespace App\Actions\Cms\Member;

use App\Models\User;

class UpdateMemberStatusAction
{
    /**
     * Handle the action.
     */
    public function handle(User $member, bool $isActive): bool
    {
        return $member->update([
            'is_active' => $isActive,
        ]);
    }
}
