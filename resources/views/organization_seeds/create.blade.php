@extends('layouts.app')

@section('content')

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">支援種別</h1>
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

          {!! Form::model($organization_seed, ['route' => ['organization_seeds.store'], 'class' => 'form-horizontal']) !!}

            <!-- /.card-header -->
            <div class="card-body">

              <p>必要事項を入力し、画面下のボタンを押してください。</p>

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援団体
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ $organization_seed->organization->name }}

                  {{ Form::hidden('organization_id', null) }}
                  {{ Form::inputError('name') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援種別（中）
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::select('support_category2_id', $support_category2s, null, ['class' => 'form-control select2']) }}
                  {{ Form::inputError('support_category2_id') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  コメント
                </label>

                <div class="col-sm-10">

                  {{ Form::textarea('comment', null, ['class' => 'form-control', 'id' => 'matchingCondition']) }}
                  {{ Form::inputError('comment') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


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
