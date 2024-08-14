@extends('layouts.app')

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">パスワード変更</h1>
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
                  <h3 class="card-title">パスワード変更</h3>
                </div>

                {!! Form::model($user, ['route' => ['password_changes.update', $user->id]]) !!}
                @method('PUT')

                <!-- /.card-header -->
                <div class="card-body">

                  <p>必要事項を入力し、画面下のボタンを押してください。</p>


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
