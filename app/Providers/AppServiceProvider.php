<?php

namespace App\Providers;

use App\Dataset;
use App\Observers\DatasetObserver;
use App\Observers\OrganizationObserver;
use App\Observers\ResourceObserver;
use App\Observers\TemplateObserver;
use App\Observers\UserObserver;
use App\Observers\WorkflowHistoryObserver;
use App\Organization;
use App\Prefecture;
use App\Report;
use App\Resource;
use App\Shelter;
use App\Template;
use App\User;
use App\WorkflowHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // mysqlのバージョンによるutf8mb4のエラーを回避する対応
        // https://laravel-news.com/laravel-5-4-key-too-long-error
        if ( ! app()->environment('production')) {
            Schema::defaultStringLength(191);
        }

        // アクセスを全てhttpsに。
        if (env('SSL_USE')) {
            $this->app['request']->server->set('HTTPS','on');
        }

        // viewでエラーメッセージ表示に使用するカスタムフォーム
        \Form::component('inputError', 'components.inputError', ['name']);

        // SQLのログ出力を本番環境以外で可能にする。
        if ( ! app()->environment('production')) {
            DB::enableQueryLog();
        }


        // Routeのリソースで自動的にセットされないモデルをバインディング
        Route::bind('admin_user', function ($value) {
            return User::find($value) ?? abort(404);
        });


    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
