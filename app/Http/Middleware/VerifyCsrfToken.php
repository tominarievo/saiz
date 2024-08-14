<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

/**
 * CSRFトークンの検証
 */
class VerifyCsrfToken extends Middleware
{
    /**
     * CRF検証から除外するURI
S     *
     * @var array
     */
    protected $except = [
        //
    ];
}
