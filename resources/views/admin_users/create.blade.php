@extends('layouts.app')

@section('content')


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">管理ユーザー</h1>
          </div><!-- /.col -->

        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- MAIN CONTENT -->
    <section class="content">
      <div class="container-fluid">

        @if (session()->has('status'))
          <div class="alert alert-success">
            {{ session('status') }}
          </div>
        @endif

        <!-- ROW -->
          <div class="row">

            <section class="col-lg-12 connectedSortable">

              <div class="card card-info">
                <div class="card-header">
                  <h3 class="card-title">管理ユーザー
                    新規作成
                  </h3>
                </div>

    {!! Form::model($user, ['route' => 'admin_users.store']) !!}
      {{ Form::hidden('is_valid', null) }}

              <!-- /.card-header -->
                <div class="card-body">

                  <p>必要事項を入力し、画面下のボタンを押してください。</p>

                  {{-- 1入力項目 --}}
                  <div class="form-group row">

                    <label for="inputEmail3" class="col-sm-2 col-form-label">
                      権限
                    </label>

                    <div class="col-sm-10">

                      <label>

                      {{ Form::checkbox('is_writer', 1, null, ['class' => 'form-']) }}
                        日報記入のみ
                      </label>
                      {{ Form::inputError('is_writer') }}

                    </div>
                  </div>
                  {{-- / 1入力項目 --}}

                  {{-- 1入力項目 --}}
                  <div class="form-group row">

                    <label for="inputEmail3" class="col-sm-2 col-form-label">
                      ユーザー名
                      <span class="right badge badge-danger">必須</span>
                    </label>

                    <div class="col-sm-10">

                      {{ Form::text('name', null, ['class' => 'form-control']) }}
                      {{ Form::inputError('name') }}

                    </div>
                  </div>
                  {{-- / 1入力項目 --}}


                  {{-- 1入力項目 --}}
                  <div class="form-group row">

                    <label for="inputEmail3" class="col-sm-2 col-form-label">
                      メールアドレス
                      <span class="right badge badge-danger">必須</span>
                    </label>

                    <div class="col-sm-10">

                      {{ Form::text('username', null, ['class' => 'form-control']) }}
                      {{ Form::inputError('username') }}

                    </div>
                  </div>
                  {{-- / 1入力項目 --}}

                  {{-- 1入力項目 --}}
                  <div class="form-group row">

                    <label for="inputEmail3" class="col-sm-2 col-form-label">
                      パスワード
                      <span class="right badge badge-danger">必須</span>
                    </label>

                    <div class="col-sm-10">

                      {{ Form::password('password', ['class' => 'form-control']) }}
                      {{ Form::inputError('password') }}

                    </div>
                  </div>
                  {{-- / 1入力項目 --}}

                  {{-- 1入力項目 --}}
                  <div class="form-group row">

                    <label for="inputEmail3" class="col-sm-2 col-form-label">
                      パスワード(確認)
                      <span class="right badge badge-danger">必須</span>
                    </label>

                    <div class="col-sm-10">
                      {{ Form::password('password_confirmation', ['class' => 'form-control']) }}
                      {{ Form::inputError('password_confirmation') }}

                    </div>
                  </div>
                  {{-- / 1入力項目 --}}
                </div>

                <div class="card-footer">

                  <button name="submit" class="btn btn-info" value="submit">登録</button>
                </div>

              {!! Form::close() !!}

              <!-- END BASIC TABLE -->
              </div>
          </div>
          <!-- END ROW -->


    </div>
    <!-- END CONTAINER FLUID -->
  </div>
  <!-- END MAIN CONTENT -->

    </section>
@endsection
