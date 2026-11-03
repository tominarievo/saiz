<?php

use App\Http\Controllers\Api\DisasterIntelligenceController;
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

Route::prefix('v1/disaster')->group(function () {
    Route::get('roads', [DisasterIntelligenceController::class, 'roads'])->name('api.v1.disaster.roads');
    Route::get('waste-sites', [DisasterIntelligenceController::class, 'wasteSites'])->name('api.v1.disaster.waste_sites');
});
