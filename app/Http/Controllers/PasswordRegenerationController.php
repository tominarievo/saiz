<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRegenerationRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * パスワードの再発行の新パスワード設定のコントローラークラス
 */
class PasswordRegenerationController extends Controller
{

    /**
     * パスワードの再発行の新パスワード設定のためのフォームを表示するメソッド
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $hash = $request->input('hash');

        $user = null;

        if (blank($hash)) {
            $request->session()->flash('error', '不正なURLです。');
            return view("password_regenerations.create", compact("user"));
        }

        $now = date("Y-m-d H:i:s");

        $user = User::query()
            ->where("reset_password_access_key", "=", $hash)
            ->where('reset_password_expire_at', ">=", $now)
            ->first();

        if ( ! $user) {
            $request->session()->flash('error', 'ユーザーが存在しないか、URLの有効期限が切れています。');
        }

        return view("password_regenerations.create", compact("user", "hash"));
    }

    /**
     * パスワードの再発行の新パスワード設定を保存するメソッド
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PasswordRegenerationRequest $request)
    {
        $hash = $request->input('hash');

        $user = null;

        if (blank($hash)) {
            $request->session()->flash('error', '不正なURLです。');
            return view("password_regenerations.create", compact("user"));
        }

        $now = date("Y-m-d H:i:s");

        $user = User::query()
            ->where("reset_password_access_key", "=", $hash)
            ->where('reset_password_expire_at', ">=", $now)
            ->first();

        if ( ! $user) {
            $request->session()->flash('error', 'ユーザーが存在しないか、URLの有効期限が切れています。');
        }

        // トランザクションの開始
        DB::beginTransaction();

        try {
            $user->password = Hash::make($request->input('password'));
            $user->reset_password_access_key = null;
            $user->reset_password_expire_at  = null;

            $user->save();

            // トランザクションのコミット
            DB::commit();

        } catch (\Throwable $e) {

            // トランザクションのロールバック
            DB::rollBack();

            // 例外を処理するか、ログに記録するなどの対応を行う
            // throw $e; // 例外を再スローする場合
            Log::error($e->getMessage());

            abort(500);
        }

        return redirect(route("password_regenerations.complete"));
    }
}
