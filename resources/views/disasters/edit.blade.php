@extends('layouts.app')

@section('content')

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">災害情報</h1>
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
              <h3 class="card-title">災害情報
                編集
              </h3>
            </div>

          {!! Form::model($disaster, ['route' => ['disasters.update', $disaster->id], 'class' => 'form-horizontal', ]) !!}
          @method('PUT')

            <!-- /.card-header -->
            <div class="card-body">

              <p>必要事項を入力し、画面下のボタンを押してください。</p>



              {{-- 1入力項目 --}}
{{--              <div class="form-group row">--}}

{{--                <label for="inputEmail3" class="col-sm-2 col-form-label">--}}
{{--                  ステータス--}}
{{--                </label>--}}

{{--                <div class="col-sm-10">--}}
{{--                  <label for="status" class="">--}}
{{--                    {{ Form::checkbox('status', true, null, ['class' => '', 'id' => 'status']) }} 有効--}}
{{--                  </label>--}}

{{--                </div>--}}
{{--              </div>--}}
              {{-- / 1入力項目 --}}



              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  激甚災害
                </label>

                <div class="col-sm-10">
                  <label for="is_catastrophic_disaster" class="">
                    {{ Form::checkbox('is_catastrophic_disaster', true, null, ['class' => '', 'id' => 'is_catastrophic_disaster']) }} 激甚災害
                  </label>

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  年
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_2', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_2') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  発生日
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::text('event_date', null, ['class' => 'form-control datepicker', 'id' => '']) }}
                  {{ Form::inputError('event_date') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}



              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  災害種別
                </label>

                <div class="col-sm-10">

                  @foreach($disaster_types as $disaster_type)
                    <label>
                      {{ Form::checkbox('disaster_type_ids[]', $disaster_type->id, null, ['class' => '', 'id' => '']) }}

                      {{ $disaster_type->name }}
                    </label>
                  @endforeach

                  {{ Form::inputError('disaster_type_ids') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  災害名
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::text('name', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('name') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  備考
                </label>

                <div class="col-sm-10">

                  {{ Form::textarea('npo_col_3', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_3') }}

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

@section('script')


@endsection
