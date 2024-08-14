@extends('layouts.login_layout')

@section('content')

  <div class="login-box">

    <div class="login-logo">
      <img src="/img/logo.png" style="width: 300px">
    </div>

    <!-- /.login-logo -->
    <div class="card">
      <div class="card-body login-card-body">
        @if( ! session('error'))

          <h4 class="l-page-ttl">パスワード再設定</h4>

          <p class="text-gray">新しいパスワードを入力してください。</p>

          @if ($errors->has('password'))
              <div class="alert alert-danger" role="alert">
                <strong>{{ $errors->first('password') }}</strong>
              </div>
          @endif

          @if ($errors->has('password_confirmation'))
              <div class="alert alert-danger" role="alert">
                <strong>{{ $errors->first('password_confirmation') }}</strong>
              </div>
          @endif


          <form id="login-form" method="POST" action="{{ route('password_regenerations.store') }}">
            @csrf
            <input type="hidden" name="hash" value="{{ old('hash', $hash) }}">

          <div class="input-group mb-3">

            <input type="password" name="password" value="{{ old('password') }}" class="form-control {{ $errors->has('password') ? 'error' : '' }}" placeholder="新しいパスワードを入力" required/>

            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
              </div>
            </div>
          </div>

          <div class="input-group mb-3">

            <input type="password" name="password_confirmation" value="{{ old('password_confirmation') }}" class="form-control {{ $errors->has('password_confirmation') ? 'error' : '' }}" placeholder="新しいパスワード確認用を入力" required/>

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
              <button type="submit" class="btn btn-primary btn-block btn-login">変更</button>
            </div>
            <!-- /.col -->
          </div>
        </form>

      </div>
      <!-- /.login-card-body -->

      @else
        <div class="alert alert-danger">
          {{ session('error') }}
        </div>
      @endif

    </div>
  </div>
  <!-- /.login-box -->

@endsection
