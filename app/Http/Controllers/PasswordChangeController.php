<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordChangeRequest;
use App\User;
use Illuminate\Support\Facades\Hash;

/**
 * パスワード変更に関する操作を処理するコントローラー
 */
class PasswordChangeController extends Controller
{
    /**
     * パスワード変更画面を表示する
     *
     * @param  int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View  レスポンス
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);

        // 権限チェック
        $this->authorize('passsword_change', $user);

        return view('password_changes.edit', compact('user'));
    }

    /**
     * パスワードを変更する
     *
     * @param  PasswordChangeRequest  $request
     * @param  int $id
     * @return \Illuminate\Http\RedirectResponse  レスポンス
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(PasswordChangeRequest $request, $id)
    {
        $user = User::findOrFail($id);

        // 権限チェック
        $this->authorize('passsword_change', $user);

        $user->password = Hash::make($request->input('password', ['rounds' => 13]));
        $user->save();

        $request->session()->flash('status', '更新に成功しました。');

        return redirect(route('home'))->with('success_message', 'パスワードを変更しました。');
    }
}
