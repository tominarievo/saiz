@extends('layouts.login_layout')

@section('content')
  <script>
  document.addEventListener("DOMContentLoaded", function() {
      // ログインボタンをクリックした後に無効化する関数
      function disableLoginButton() {
          document.querySelector('.btn-login').setAttribute('disabled', 'true');
      }

      // CSRFトークンを更新する関数
      function refreshCsrfToken() {
          fetch('/refresh-csrf')
              .then(response => response.json())
              .then(data => {
                  // CSRFトークンの値を更新
                  document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.csrfToken);
                  // すべてのフォームのCSRFトークン入力フィールドを更新
                  document.querySelectorAll('input[name="_token"]').forEach(el => el.value = data.csrfToken);
              })
              .catch(error => console.error('Error refreshing CSRF token:', error));
      }

      // ログインフォームが送信されたときの処理
      document.querySelector('#login-form').addEventListener('submit', function(event) {
          // ボタンを無効化
          disableLoginButton();
          // CSRFトークンを更新
          refreshCsrfToken();
      });

      // 30分ごとにCSRFトークンを更新
      setInterval(refreshCsrfToken, 1800000); // 1800000ミリ秒 = 30分
  });
  </script>

  <div class="login-box">

    <div class="login-logo">
      <img src="/img/logo.png" style="width: 300px">
    </div>

    <!-- /.login-logo -->
    <div class="card">
      <div class="card-body login-card-body">


        @if ($errors->has('username'))
          <div class="alert alert-danger" role="alert">
            <strong>{{ $errors->first('username') }}</strong>
          </div>
        @endif

        @if ($errors->has('password'))
          <span class="invalid-feedback">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
        @endif


        <form id="login-form" method="POST" action="{{ route('login') }}">
          @csrf

          <div class="input-group mb-3">

            <input type="text" name="username" value="{{ old('username') }}" id="user-id" class="form-control {{ $errors->has('username') ? 'error' : '' }}" placeholder="メールアドレスを入力" required/>

            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
              </div>
            </div>
          </div>

          <div class="input-group mb-3">

            <input type="password" name="password" value="" id="signin-password" class="form-control {{ $errors->has('password') ? 'error' : '' }}" placeholder="パスワードを入力" required />

            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-8">

            </div>
            <!-- /.col -->
            <div class="col-4">
              <button type="submit" class="btn btn-primary btn-block btn-login">ログイン</button>
            </div>
            <!-- /.col -->
          </div>
        </form>

      </div>
      <!-- /.login-card-body -->

    </div>
  </div>


  <p class="m-3">
    <a href="<?php echo route('new_passwords.create'); ?>">パスワードをお忘れの場合</a>
  </p>

  <!-- /.login-box -->

@endsection
