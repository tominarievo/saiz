<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSignupEnabled
{
    /**
     * オンラインサインアップが停止中の場合はアクセスを拒否する。
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!config('signup.enabled')) {
            abort(403, '現在、オンラインサインアップを停止しています。');
        }

        return $next($request);
    }
}
