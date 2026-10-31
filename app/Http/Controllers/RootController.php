<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class RootController extends Controller
{
    public function __invoke()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        return redirect()->route('home');
    }
}
