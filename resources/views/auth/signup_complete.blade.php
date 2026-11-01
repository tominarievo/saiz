@extends('layouts.login_layout')

@section('content')

  <div class="login-box">

    <div class="login-logo">
      <img src="/img/logo.png" style="width: 300px">
    </div>

    <!-- /.login-logo -->
    <div class="card">
      <div class="card-body login-card-body">

        <h4 class="l-page-ttl">利用登録のお申し込み</h4>
        <p class="text-gray pb-4">
          利用登録のお申し込みを受け付けました。<br>
          ご入力のメールアドレスに受付メールをお送りしました。<br>
          事務局の承認が完了しましたら、あらためてご連絡いたします。<br>
          承認が完了するまではログインいただけませんのでご了承ください。
        </p>
        <hr>
        <p class="text-center mt-5">
          <a href="<?php echo route('login'); ?>">ログイン画面に戻る</a>
        </p>

      </div>
      <!-- /.login-card-body -->
    </div>
  </div>
  <!-- /.login-box -->

@endsection
