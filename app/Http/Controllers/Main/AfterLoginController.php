<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;

class AfterLoginController extends Controller
{
    public function __invoke()
    {
        if (auth()->user()->hasRole(['superadmin', 'admin'])) {
            return to_route('cms.dashboard');
        }

        return to_route('home');
    }
}
