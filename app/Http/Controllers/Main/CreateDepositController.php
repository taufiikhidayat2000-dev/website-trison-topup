<?php

namespace App\Http\Controllers\Main;

use App\Actions\Main\CreateDepositAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Main\CreateDepositRequest;

class CreateDepositController extends Controller
{
    public function __invoke(CreateDepositRequest $request, CreateDepositAction $action)
    {
        try {
            $deposit = $action->handle($request->user(), $request->integer('amount'), $request->string('channel')->toString());
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return to_route('wallet.deposits.show', ['deposit' => $deposit]);
    }
}
