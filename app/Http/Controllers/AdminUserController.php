<?php

namespace App\Http\Controllers;

use App\DatasetUserOrganization;
use App\Http\Requests\AdminUserRequest;
use App\Http\Requests\UserRequest;
use App\Mail\UserPasswordRepublishMail;
use App\Organization;
use App\OrganizationClosureTree;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 管理者ユーザーコントローラー
 */
class AdminUserController extends Controller
{
    /**
     * ユーザー一覧を表示する
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = User::whereNull('organization_id')->paginate(100);

        return view('admin_users.index', compact('users'));
    }

    /**
     * 新しいリソースを作成するためのフォームを表示する
     *
     * @param Request $request
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request)
    {

        $user = new User();
        $user->is_valid = true;

        return view('admin_users.create', compact('user'));
    }

    /**
     * 新しいリソースを保存する
     *
     * @param AdminUserRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(AdminUserRequest $request)
    {
        $user = new User();
        $user->organization_id = null;
        $user->is_valid = filled($request->input('is_valid')) ? true : false;
        $user->name = $request->input('name');
        $user->username = $request->input('username');
        $user->password = Hash::make($request->input('password'));
        $user->is_writer = $request->input("is_writer") == "1";
        $user->save();

        $request->session()->flash('status', 'ユーザーの登録に成功しました。');

        return redirect(route('admin_users.index'));
    }

    /**
     * 指定されたリソースを編集するためのフォームを表示する
     *
     * @param User $user
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(User $user)
    {
        return view('admin_users.edit', compact('user'));
    }

    /**
     * 指定されたリソースを更新する
     *
     * @param AdminUserRequest $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(AdminUserRequest $request, User $user)
    {
        $user->is_valid = filled($request->input('is_valid')) ? true : false;
        $user->name = $request->input('name');
        $user->username = $request->input('username');
        $user->is_writer = $request->input("is_writer") == "1";

        // パスワードは入力があり、DB上のハッシュ値では無い場合にのみセット
        if (filled($request->input('password'))
            && ($request->input('password') !== $user->password)) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        $request->session()->flash('status', 'ユーザーの更新に成功しました。');

        return redirect(route('admin_users.index'));
    }

    /**
     * 指定されたリソースを削除する
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, User $user)
    {

        $user->delete();

        $request->session()->flash('status', 'ユーザーの削除に成功しました。');

        return redirect(route('admin_users.index'));
    }
}
