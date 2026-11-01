<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SignupController;
use App\Http\Controllers\DisasterController;
use App\Http\Controllers\DisasterShelterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationSeedController;
use App\Http\Controllers\OrganizationViewController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PasswordRegenerationApplicationController;
use App\Http\Controllers\PasswordRegenerationController;
use App\Http\Controllers\PlanCommentController;
use App\Http\Controllers\PlanCommentReadController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SeedController;
use App\Http\Controllers\ShelterController;
use App\Http\Controllers\ShelterViewController;
use App\Http\Controllers\SupportCategory1Controller;
use App\Http\Controllers\SupportCategory2Controller;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UtilController;
use App\Http\Controllers\CsrfTokenController;
use App\Http\Controllers\RootController;
use Illuminate\Support\Facades\Route;

Route::get('/', RootController::class);

// Authentication Routes...
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// 利用規約
Route::view('terms', 'legal.terms')->name('terms');

// オンラインサインアップ（支援団体＋代表ユーザーの新規登録）
Route::get('signup', [SignupController::class, 'create'])->middleware('guest')->name('signup.create');
Route::post('signup', [SignupController::class, 'store'])->middleware(['guest', 'throttle:5,1'])->name('signup.store');
Route::view('signup/complete', 'auth.signup_complete')->middleware('guest')->name('signup.complete');

// パスワード再発行受付
Route::view('/new_passwords/complete', 'password_regeneration_applications.complete')->name('new_passwords.complete');
Route::resource('new_passwords', PasswordRegenerationApplicationController::class);

// パスワード再発行
Route::view('/password_regenerations/complete', 'password_regenerations.complete')->name('password_regenerations.complete');
Route::resource('password_regenerations', PasswordRegenerationController::class);

// CSRF更新
Route::get('/refresh-csrf', CsrfTokenController::class);

Route::middleware(['auth', 'my-common'])->group(function () {
    // 災害概況画面
    Route::get('/overview', [OverviewController::class, 'index'])->name('overview');

    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // TODO 削除
    Route::view('/ajax_search_sample', 'ajax_search_sample')->name('ajax_search_sample');

    Route::get('/api/shelters/index', [ShelterController::class, 'index'])->name('api.shelters.index');

    Route::resource('organizations', OrganizationController::class);
    Route::resource('shelters', ShelterController::class);
    Route::resource('disasters', DisasterController::class);
    Route::resource('support_category1s', SupportCategory1Controller::class);
    Route::resource('support_category2s', SupportCategory2Controller::class);
    Route::resource('reports', ReportController::class);

    Route::get('/my_reports', [ReportController::class, 'index'])->name('my_reports.index');

    Route::resource('plans', PlanController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::resource('shelter_views', ShelterViewController::class);
    Route::resource('organization_views', OrganizationViewController::class);

    Route::resource('organization_seeds', OrganizationSeedController::class);

    Route::resource('users', UserController::class);
    Route::resource('admin_users', AdminUserController::class);
    Route::resource('password_changes', PasswordChangeController::class)->only(['edit', 'update']);

    Route::resource('seeds', SeedController::class);

    Route::get('/disaster_shelters/edit/{disaster_id}', [DisasterShelterController::class, 'edit'])->name('disaster_shelters.edit');
    Route::put('/disaster_shelters/update/{disaster_id}', [DisasterShelterController::class, 'update'])->name('disaster_shelters.update');

    Route::get('/information/{information}/file/{index}', [InformationController::class, 'file'])->name('information.files.show');
    Route::resource('information', InformationController::class);

    Route::post('/plan_comments/store', [PlanCommentController::class, 'store'])->name('api.plan_comments.store');
    Route::post('/plan_comments/delete', [PlanCommentController::class, 'delete'])->name('api.plan_comments.delete');

    Route::post('/plan_comment_reads/update', [PlanCommentReadController::class, 'update'])->name('api.plan_comment_reads.update');

    // アップロード系
    Route::post('/admin/information_file_upload', [UtilController::class, 'uploadInformationFile'])->name('upload.information.file');
});
