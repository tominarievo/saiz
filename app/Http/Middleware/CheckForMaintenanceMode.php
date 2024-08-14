<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode as Middleware;

/**
 * メンテナンスモードのチェック
 */
class CheckForMaintenanceMode extends Middleware
{
    /**
     * The URIs that should be excluded from maintenance.
     *
     * @var array
     */
    protected $except = [
        //
    ];
}
