<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;

class HomeRedirectController extends Controller
{
    public function __invoke()
    {
        return to_route('cms.dashboard');
    }
}
