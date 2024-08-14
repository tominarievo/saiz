<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Mail\UserCreatedMail;
use App\Organization;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ユーザー新規登録
=======
 */
class UserController extends Controller
{
    /**
     * ユーザー画面を表示する
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $user = new User();
        $user->is_valid = true;

        $organization_id = $request->input('organization_id');
        $organization = Organization::findOrFail($organization_id);

        $user->organization_id = $organization->id;
        $user->email = '';

        return view('users.create', compact('user'));
    }

    /**
     * ユーザーを登録する
     *
     * @param UserRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $organization_id = $request->input('organization_id');
    
        // トランザクションの開始
        DB::beginTransaction();
    
        try {
            $user = new User();
            $user->organization_id = $organization_id;
            $user->is_valid = filled($request->input('is_valid')) ? true : false;
            $user->name = $request->input('name');
            $user->username = $request->input('username');
            $user->password = Hash::make($request->input('password'));
            $user->save();
    
            /*
             * メールを送信
             */
    
            $data = [
                "mail_data" => $user,
                ""
            ];
    
            // BCCで追加する受信者のリスト
            $bccRecipients = explode(',', env('MAIL_BCC_DEFAULT', ''));
    
            // ユーザーへユーザー情報の通知メール
            if (filled($user->username)) {
                Mail::to($user->username)
                    ->bcc($bccRecipients)
                    ->send(new UserCreatedMail($data));
            }
    
            $request->session()->flash('status', 'ユーザーの登録に成功しました。');
    
            // トランザクションのコミット
            DB::commit();
        } catch (\Throwable $e) {
            // トランザクションのロールバック
            DB::rollBack();
    
            // ログに詳細情報を記録
            Log::error("User registration failed: " . $e->getMessage(), [
                'organization_id' => $organization_id,
                'username' => $request->input('username'),
                'name' => $request->input('name'),
                'stack' => $e->getTraceAsString()
            ]);
    
            // エラー発生時のユーザーへのフィードバック
            $request->session()->flash('error', 'ユーザー登録に失敗しました。管理者に連絡してください。');
    
            // エラーページへリダイレクト
            abort(500, 'ユーザー登録に失敗しました。');
        }
    
        return redirect(route('organizations.show', ["organization" => $user->organization_id]));
    }
    
    /**
     * ユーザーを編集する
     *
     * @param User $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {

        return view('users.edit', compact('user'));
    }

    /**
     * ユーザーを更新する
     *
     * @param UserRequest $request
     * @param User $user
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, User $user)
    {

        // トランザクションの開始
        DB::beginTransaction();

        try {

            $user->is_valid = filled($request->input('is_valid')) ? true : false;
            $user->name = $request->input('name');
            $user->username = $request->input('username');

            // パスワードは入力があり、DB上のハッシュ値では無い場合にのみセット
            if (filled($request->input('password'))
                && ($request->input('password') !== $user->password)) {
                $user->password = Hash::make($request->input('password'));
            }

            $user->save();

            $request->session()->flash('status', 'ユーザーの更新に成功しました。');

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

        return redirect(route('organizations.show', ["organization" => $user->organization_id]));
    }

    /**
     * ユーザーを削除する
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, User $user)
    {
        $user->delete();

        $request->session()->flash('status', 'ユーザーの削除に成功しました。');

        return redirect(route('organizations.show', ["organization" => $user->organization_id]));
    }
}
