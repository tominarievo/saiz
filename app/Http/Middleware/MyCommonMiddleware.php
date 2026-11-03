<?php

namespace App\Http\Middleware;

use App\Support\ShelterStatusSummary;
use Closure;

/**
 * 共通ミドルウェア
 */
class MyCommonMiddleware
{
    /**
     * リクエストの処理
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        \View::share('signal_count', ShelterStatusSummary::today());

        return $next($request);
    }
}
