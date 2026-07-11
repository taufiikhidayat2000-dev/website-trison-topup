<?php

namespace App\Actions\Cms\Member;

use App\Models\User;
use Illuminate\Support\Str;

class ResetMemberPasswordAction
{
    /**
     * Handle the action. Generates a random password, saves the hash, and
     * returns the plaintext ONLY so the caller can show it once — it is
     * never persisted or logged in plaintext anywhere.
     */
    public function handle(User $member): string
    {
        $plainPassword = Str::password(12);

        // The 'hashed' cast on User::password hashes this automatically on save.
        $member->update([
            'password' => $plainPassword,
        ]);

        activity()
            ->performedOn($member)
            ->log('Password reset by admin');

        return $plainPassword;
    }
}
