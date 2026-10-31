<?php

namespace App\Http\Controllers;

class CsrfTokenController extends Controller
{
    public function __invoke()
    {
        return csrf_token();
    }
}
