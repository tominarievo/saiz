<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignupRequest;
use App\Mail\SignupReceivedMail;
use App\Organization;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * オンラインサインアップ（支援団体＋代表ユーザーの新規登録）を処理するコントローラー
 *
 * 登録されたユーザーは is_valid=false（承認待ち）で作成し、
 * 全体管理者が有効化するまでログインできない。
 */
class SignupController extends Controller
{
    /**
     * 新しいコントローラーインスタンスを生成します。
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * サインアップフォームを表示する
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('auth.signup');
    }

    /**
     * サインアップを受け付け、支援団体と代表ユーザーを登録する
     *
     * @param SignupRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(SignupRequest $request)
    {
        DB::beginTransaction();

        try {
            $organization = new Organization();
            $organization->name = $request->input('organization_name');
            $organization->status = true;
            $organization->save();

            $user = new User();
            $user->organization_id = $organization->id;
            $user->is_valid = false;
            $user->is_system_admin = false;
            $user->name = $request->input('name');
            $user->username = $request->input('email');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));
            $user->save();

            /*
             * 受付メールを送信
             */
            $bccRecipients = config('mail.bcc', []);

            if (filled($user->username)) {
                Mail::to($user->username)
                    ->bcc($bccRecipients)
                    ->send(new SignupReceivedMail($user));
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            \App\Support\SecurityAudit::record('signup.failed', null, 'error');

            abort(500, 'サインアップに失敗しました。');
        }

        $request->session()->flash('status', 'サインアップを受け付けました。');

        return redirect(route('signup.complete'));
    }
}
