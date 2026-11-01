<?php

use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the bootstrap/app.php file.
|
*/

Route::middleware('auth:api')->get('/user', [UserController::class, 'show']);
