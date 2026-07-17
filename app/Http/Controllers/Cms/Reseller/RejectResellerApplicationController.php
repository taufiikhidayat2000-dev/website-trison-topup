<?php

namespace App\Http\Controllers\Cms\Reseller;

use App\Actions\Cms\Reseller\RejectResellerApplicationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Reseller\RejectResellerApplicationRequest;
use App\Models\Reseller\ResellerApplication;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectResellerApplicationController extends Controller
{
    public function __invoke(RejectResellerApplicationRequest $request, ResellerApplication $application, RejectResellerApplicationAction $action)
    {
        Gate::authorize('update'.ResellerApplication::class);

        if (! $application->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan ini sudah diproses sebelumnya.',
            ]);
        }

        $action->handle($application, $request->validated('reason'), $request->user());

        return back()->with('success', 'Pengajuan reseller ditolak.');
    }
}
