<?php

namespace App\Http\Controllers\Cms\Member;

use App\Actions\Cms\Member\ResetMemberPasswordAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ResetMemberPasswordController extends Controller
{
    public function __invoke(User $member, ResetMemberPasswordAction $action)
    {
        Gate::authorize('update'.User::class);

        $newPassword = $action->handle($member);

        return back()->with('newPassword', $newPassword);
    }
}
