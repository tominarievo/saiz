@extends('layouts.app')

@section('content')

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">支援団体</h1>
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

          {!! Form::model($organization, ['route' => ['organizations.update', $organization->id], 'class' => 'form-horizontal', ]) !!}
          @method('PUT')

            <!-- /.card-header -->
            <div class="card-body">

              <p>必要事項を入力し、画面下のボタンを押してください。</p>

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  名称
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::text('name', null, ['class' => 'form-control', 'id' => 'matchingCondition']) }}
                  {{ Form::inputError('name') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  活動状況
                </label>

                <div class="col-sm-10">
                  <label for="customCheckbox1" class="">
                    {{ Form::checkbox('status', '1', null, ['class' => '', 'id' => 'customCheckbox1']) }} 支援活動中
                  </label>

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  代表者
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_2', null, ['class' => 'form-control', 'id' => 'matchingCondition']) }}
                  {{ Form::inputError('npo_col_2') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  市区町村
                </label>

                <div class="col-sm-10">

                  {{ Form::select('local_government_id', $local_government_pulldowns, null, ['class' => 'select2 form-control', 'placeholder' => '選択してください']) }}
                  {{ Form::inputError('local_government_id') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  住所(町丁目以降)
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_3', null, ['class' => 'form-control', 'id' => 'matchingCondition']) }}
                  {{ Form::inputError('npo_col_3') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  電話番号
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_4', null, ['class' => 'form-control', 'id' => 'matchingCondition']) }}
                  {{ Form::inputError('npo_col_4') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  公開用メモ
                </label>

                <div class="col-sm-10">

                  {{ Form::textarea('description', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('description') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  備考
                </label>

                <div class="col-sm-10">

                  {{ Form::textarea('npo_col_5', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_5') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              <hr>

              @can('show_admin_information', $organization)
              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  事務局利用欄 <span class="badge badge-dark">非公開</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::textarea('npo_col_6', null, ['class' => 'form-control', 'id' => 'matchingCondition']) }}
                  {{ Form::inputError('npo_col_6') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}
              @endcan




            </div>

            <div class="card-footer">

              <button name="submit" class="btn btn-primary" value="submit">登録</button>
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
