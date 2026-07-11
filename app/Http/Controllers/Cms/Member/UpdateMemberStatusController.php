<?php

namespace App\Http\Controllers\Cms\Member;

use App\Actions\Cms\Member\UpdateMemberStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Member\UpdateMemberStatusRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateMemberStatusController extends Controller
{
    public function __invoke(UpdateMemberStatusRequest $request, User $member, UpdateMemberStatusAction $action)
    {
        Gate::authorize('update'.User::class);

        $action->handle($member, $request->boolean('is_active'));

        return back()->with('success', $request->boolean('is_active') ? 'Member diaktifkan.' : 'Member dinonaktifkan.');
    }
}
