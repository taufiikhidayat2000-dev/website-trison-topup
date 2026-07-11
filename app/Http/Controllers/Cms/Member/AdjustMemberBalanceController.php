<?php

namespace App\Http\Controllers\Cms\Member;

use App\Actions\Cms\Member\AdjustMemberBalanceAction;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Member\AdjustBalanceRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AdjustMemberBalanceController extends Controller
{
    public function __invoke(AdjustBalanceRequest $request, User $member, AdjustMemberBalanceAction $action)
    {
        Gate::authorize('update'.User::class);

        try {
            $action->handle($member, $request->validated(), $request->user());
        } catch (InsufficientBalanceException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', 'Saldo berhasil disesuaikan.');
    }
}
