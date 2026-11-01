<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

define('LARAVEL_START', microtime(true));
define('MY_ORGANIZATION_ID', 1);

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the application and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

(require_once __DIR__.'/../bootstrap/app.php')->handleRequest(
    Illuminate\Http\Request::capture()
);

/*
 * パフォーマンス計測
 */

define('MY_LARAVEL_END', microtime(true));

// 以下、追加
$processTime = MY_LARAVEL_END - LARAVEL_START;

\Illuminate\Support\Facades\Log::info("start:".LARAVEL_START." end:".MY_LARAVEL_END." time:".$processTime);
