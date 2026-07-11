<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Wallet\Deposit;
use Illuminate\Support\Facades\Auth;

class ShowDepositController extends Controller
{
    public function __invoke(Deposit $deposit)
    {
        abort_unless($deposit->user_id === Auth::id(), 403);

        return inertia('main/DepositShow', [
            'deposit' => $deposit,
        ]);
    }
}
