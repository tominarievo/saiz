<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Request;

Route::get('/', function () {

    if ( ! Auth::check()) {
        return redirect(route('login'));
    }
    else {
        return redirect(route('home'));
    }
});

// Authentication Routes...
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

# パスワード再発行受付
Route::view('/new_passwords/complete', 'password_regeneration_applications.complete')->name('new_passwords.complete');
Route::resource('new_passwords', 'PasswordRegenerationApplicationController');

# パスワード再発行
Route::view('/password_regenerations/complete', 'password_regenerations.complete')->name('password_regenerations.complete');
Route::resource('password_regenerations', 'PasswordRegenerationController');

//  csrf update by tominari
Route::get('/refresh-csrf', function() {
    return csrf_token();
});

Route::get('file_download', [\App\Http\Controllers\UtilController::class, 'frontFileDownload'])->name('utils.front_file_download');

Route::middleware(['auth', 'my-common'])->group(function () {

    // 災害概況画面
    Route::get('/overview', 'OverviewController@index')->name('overview');

    Route::get('/home', 'HomeController@index')->name('home');

    // TODO 削除
    Route::view('/ajax_search_sample', 'ajax_search_sample')->name('ajax_search_sample');


    Route::get('/api/shelters/index', 'ShelterController@index')->name('api.shelters.index');


    Route::resource('organizations', 'OrganizationController');
    Route::resource('shelters', 'ShelterController');
    Route::resource('disasters', 'DisasterController');
    Route::resource('support_category1s', 'SupportCategory1Controller');
    Route::resource('support_category2s', 'SupportCategory2Controller');
    Route::resource('reports', 'ReportController');

    Route::get('/my_reports', 'ReportController@index')->name('my_reports.index');

    Route::resource('plans', 'PlanController');

    Route::resource('shelter_views', 'ShelterViewController');
    Route::resource('organization_views', 'OrganizationViewController');

    Route::resource('organization_seeds', 'OrganizationSeedController');

    Route::resource('users', 'UserController');
    Route::resource('admin_users', 'AdminUserController');// admin_userのモデルバインディングはAppServiceProviderで設定している
    Route::resource('password_changes', 'PasswordChangeController')->only(['edit', 'update']);


    Route::resource('seeds', 'SeedController');

    Route::get('/disaster_shelters/edit/{disaster_id}', 'DisasterShelterController@edit')->name('disaster_shelters.edit');
    Route::put('/disaster_shelters/update/{disaster_id}', 'DisasterShelterController@update')->name('disaster_shelters.update');

    Route::resource('/information', "InformationController");

    Route::post('/plan_comments/store', 'PlanCommentController@store')->name('api.plan_comments.store');
    Route::post('/plan_comments/delete', 'PlanCommentController@delete')->name('api.plan_comments.delete');

    Route::post('/plan_comment_reads/update', 'PlanCommentReadController@update')->name('api.plan_comment_reads.update');

    // アップロード系
    Route::post('/admin/information_file_upload', 'UtilController@uploadInformationFile')->name('upload.information.file');

});
