<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRegenerationApplicationRequest;
use App\Mail\PasswordRegenerationMail;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * パスワードの再発行依頼画面のコントローラークラス
 */
class PasswordRegenerationApplicationController extends Controller
{
    /**
     * パスワードの再発行依頼のためのフォームを表示するメソッド
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = new User();

        return view('password_regeneration_applications.create', compact('user'));
    }

    /**
     * パスワードの再発行依頼を実行し、メールを送信するメソッド
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PasswordRegenerationApplicationRequest $request)
    {

        // トランザクションの開始
        DB::beginTransaction();

        try {

            $user = User::where('username', $request->input("username"))
                ->first();

            // メールアドレスが存在しない場合でもユーザーには成功したように表示する。
            if ( ! $user) {
                DB::rollBack();
                $request->session()->flash('status', 'ご登録のメールアドレスにパスワード再設定メールを送信しました。');

                return redirect(route("new_passwords.complete"));
            }

            // UUIDの発行
            $user->reset_password_access_key = Str::uuid();
            $user->reset_password_expire_at  = date("Y-m-d H:i:s", strtotime("+24 hours"));
            $user->save();

            $url = route('password_regenerations.create').'?hash='.$user->reset_password_access_key;

            /*
             * メールを送信
             */

            $data = [
                "user" => $user,
                "url"  => $url
            ];

            // ユーザーへユーザー情報の通知メール
            if (filled($user->username))
            {
                Mail::to($user->username)
                    ->send(new PasswordRegenerationMail($data));
            }

            $request->session()->flash('status', 'ご登録のメールアドレスにパスワード再設定メールを送信しました。');

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


        return redirect(route("new_passwords.complete"));
    }
}
