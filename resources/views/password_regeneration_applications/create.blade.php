@extends('layouts.login_layout')

@section('content')

  <div class="login-box">

    <div class="login-logo">
      <img src="/img/logo.png" style="width: 300px">
    </div>

    <!-- /.login-logo -->
    <div class="card">
      <div class="card-body login-card-body">

        <p class="text-gray">パスワードを再発行します。ご登録されているメールアドレスを入力して下さい。</p>


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


        <form id="login-form" method="POST" action="{{ route('new_passwords.store') }}">
          @csrf

          <div class="input-group mb-3">

            <input type="text" name="username" value="{{ old('username') }}" id="user-id" class="form-control {{ $errors->has('username') ? 'error' : '' }}" placeholder="メールアドレスを入力" required/>

            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-8">

            </div>
            <!-- /.col -->
            <div class="col-4">
              <button type="submit" class="btn btn-primary btn-block btn-login">送信する</button>
            </div>
            <!-- /.col -->
          </div>
        </form>

      </div>
      <!-- /.login-card-body -->
    </div>
  </div>
  <!-- /.login-box -->

@endsection
