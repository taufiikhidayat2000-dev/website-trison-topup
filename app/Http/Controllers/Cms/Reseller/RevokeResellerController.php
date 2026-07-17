<?php

namespace App\Http\Controllers\Cms\Reseller;

use App\Actions\Cms\Reseller\RevokeResellerRoleAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class RevokeResellerController extends Controller
{
    public function __invoke(User $reseller, RevokeResellerRoleAction $action)
    {
        Gate::authorize('update'.User::class);

        $action->handle($reseller);

        return back()->with('success', 'Status reseller dicabut.');
    }
}
