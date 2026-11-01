@extends('layouts.login_layout')

@section('content')

  <div class="login-box" style="width: 480px;">

    <div class="login-logo">
      <img src="/img/logo.png" style="width: 300px">
    </div>

    <!-- /.login-logo -->
    <div class="card">
      <div class="card-body login-card-body">

        <h4 class="l-page-ttl">利用登録（オンラインサインアップ）</h4>
        <p class="text-gray">
          支援団体の利用登録を承ります。必要事項を入力して登録してください。<br>
          登録後、事務局の承認をもってご利用いただけます。
        </p>

        @if ($errors->any())
          <div class="alert alert-danger" role="alert">
            <ul class="mb-0 pl-3">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('signup.store') }}">
          @csrf

          <div class="form-group">
            <label for="signup-organization-name">
              支援団体名 <span class="badge badge-danger">必須</span>
            </label>
            <input type="text" name="organization_name" id="signup-organization-name"
                   value="{{ old('organization_name') }}" class="form-control" maxlength="200" required>
          </div>

          <div class="form-group">
            <label for="signup-name">
              担当者名 <span class="badge badge-danger">必須</span>
            </label>
            <input type="text" name="name" id="signup-name"
                   value="{{ old('name') }}" class="form-control" maxlength="255" required>
          </div>

          <div class="form-group">
            <label for="signup-email">
              メールアドレス <span class="badge badge-danger">必須</span>
            </label>
            <input type="email" name="email" id="signup-email"
                   value="{{ old('email') }}" class="form-control" maxlength="255" required>
          </div>

          <div class="form-group">
            <label for="signup-password">
              パスワード（12文字以上、72バイト以内。記号・空白も使えます） <span class="badge badge-danger">必須</span>
            </label>
            <input type="password" name="password" id="signup-password" class="form-control" required>
          </div>

          <div class="form-group">
            <label for="signup-password-confirmation">
              パスワード（確認） <span class="badge badge-danger">必須</span>
            </label>
            <input type="password" name="password_confirmation" id="signup-password-confirmation" class="form-control" required>
          </div>

          <div class="form-group">
            <div class="form-check">
              <input type="checkbox" name="terms" value="1" id="signup-terms" class="form-check-input" required>
              <label class="form-check-label" for="signup-terms">
                <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">利用規約</a>を確認し、同意します
              </label>
              <small class="form-text text-muted">利用規約は別のタブで開きます。</small>
            </div>
          </div>

          {{-- ボット対策（この欄には入力しないでください） --}}
          <div style="display:none" aria-hidden="true">
            <label for="signup-website">Website</label>
            <input type="text" name="website" id="signup-website" value="" tabindex="-1" autocomplete="off">
          </div>

          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-primary btn-block">登録する</button>
            </div>
            <!-- /.col -->
          </div>
        </form>

      </div>
      <!-- /.login-card-body -->
    </div>
  </div>
  <!-- /.login-box -->

  <p class="m-3">
    <a href="<?php echo route('login'); ?>">ログイン画面に戻る</a>
  </p>

@endsection
