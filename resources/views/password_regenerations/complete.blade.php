@extends('layouts.login_layout')

@section('content')

  <div class="login-box">

    <div class="login-logo">
      <img src="/img/logo.png" style="width: 300px">
    </div>

    <!-- /.login-logo -->
    <div class="card">
      <div class="card-body login-card-body">

        <h4 class="l-page-ttl">パスワード再設定</h4>
        <p class="text-gray pb-4">パスワード再設定を完了しました。</p>
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
