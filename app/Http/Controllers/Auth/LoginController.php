<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ログインを処理するコントローラー
 */
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ログインコントローラー
    |--------------------------------------------------------------------------
    |
    | このコントローラーは、アプリケーションのユーザー認証を処理し、
    | ホーム画面にリダイレクトします。コントローラーはトレイトを使用して、
    | アプリケーションに便利な機能を提供します。
    |
    */

    use AuthenticatesUsers;

    /*
     * ログイン試行回数をカウントし、ロックをかける処理
     */
    protected $maxAttempts  = 5;
    protected $decayMinutes = 10;

    /**
     * ログイン後のリダイレクト先
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * 新しいコントローラーインスタンスを生成します。
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');

        // アカウントロックの待機時間
        $this->decayMinutes = env("MY_DECAY_MINUTES", 10);
        $this->maxAttempts  = env("MY_MAX_ATTEMPTS", 5);
    }

    /**
     * ログイン時のユーザー識別子
     */
    public function username()
    {
        return 'username';
    }

    /**
     * アプリケーションへのログインリクエストを処理します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            Log::info("ログインの試行回数制限を超えました。", ["users.username" => $request->input('username')]);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        Log::info("ログインの認証に失敗しました。", ["users.username" => $request->input('username')]);

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /* 
     * ログイン時の認証条件を追加
     * @param Request $request リクエスト
     */
    public function credentials(Request $request)
    {
        $authConditionsOrigin = $request->only($this->username(), 'password');
        $authConditionsCustom = array_merge(
            $authConditionsOrigin,
            [
                'is_valid' => true,
            ]
        );
        return $authConditionsCustom;
    }

    /**
     * ユーザーのログイン後の処理
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return void
     */
    protected function authenticated(Request $request, $user)
    {
        Log::info("ログインに成功しました。", ["users.username" => $user->username]);
    }

    /**
     * ログアウト処理を行います。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    public function logout(Request $request)
    {
        Log::info("ログアウトしました。", ["users.username" => Auth::user()->username]);

        $this->guard()->logout();

        $request->session()->invalidate();

        return $this->loggedOut($request) ?: redirect('/');
    }
}
