<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

/**
 * アプリケーションのコントローラーの基底クラス
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * 全体管理者のみ許可する
     */
    protected function authorizeAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403);
    }

    /**
     * 全体管理者または指定組織の所属ユーザーのみ許可する
     *
     * @param mixed $organizationId
     */
    protected function authorizeOrganization($organizationId): void
    {
        $user = Auth::user();

        abort_unless(
            $user && ($user->isAdmin() || (string) $user->organization_id === (string) $organizationId),
            403
        );
    }
}
