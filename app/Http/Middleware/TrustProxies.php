<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Fideloper\Proxy\TrustProxies as Middleware;

/**
 * 信頼できるプロキシとの接続のためのミドルウェア
 */
class TrustProxies extends Middleware
{
    /**
     * 信頼できるプロキシのIPアドレス
     *
     * @var array|string|null
     */
    protected $proxies;

    /**
     * 信頼できるプロキシのヘッダー
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
