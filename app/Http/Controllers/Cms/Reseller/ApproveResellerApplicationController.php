<?php

namespace App\Http\Controllers\Cms\Reseller;

use App\Actions\Cms\Reseller\ApproveResellerApplicationAction;
use App\Http\Controllers\Controller;
use App\Models\Reseller\ResellerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveResellerApplicationController extends Controller
{
    public function __invoke(Request $request, ResellerApplication $application, ApproveResellerApplicationAction $action)
    {
        Gate::authorize('update'.ResellerApplication::class);

        if (! $application->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan ini sudah diproses sebelumnya.',
            ]);
        }

        $action->handle($application, $request->user());

        return back()->with('success', 'Pengajuan reseller disetujui.');
    }
}
