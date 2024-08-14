@extends('layouts.app')

@section('content')


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">支援団体を探す</h1>
          </div><!-- /.col -->

        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

  <!-- MAIN CONTENT -->
    <section class="content">
    <div class="container-fluid">

      {{-- エラーメッセージ --}}
      @if(session('message'))
        <div class="alert alert-danger">
          {{ session('message') }}
        </div>
      @endif
      @if (session()->has('status'))
        <div class="alert alert-success">
          {{ session('status') }}
        </div>
      @endif




      <div class="row">

        <section class="col-lg-12">

          {!! Form::model($condition, ['route' => ['organizations.index'], 'class' => 'form-horizontal', 'method' => 'GET']) !!}

          <div class="card collapsed-card">
          <div class="card-header">
            <h3 class="card-title" data-card-widget="collapse"></h3>

            <div class="card-tools">
              <button type="button" class="btn btn-sm btn-default" data-card-widget="collapse"><i class="fas fa-plus"></i> 詳細な条件欄の開閉</button>
            </div>
          </div>

          <div class="card-body">



            <div class="row">
              <div class="col-sm-6">

                <div class="form-group">
                  <label>キーワード</label>

                  <div class="input-group">
                    {{ Form::text('keyword', null, ['class' => 'form-control']) }}
                  </div>

                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">

                  <label>都道府県</label>

                  <div class="form-group">

                    {{ Form::select('prefecture_id', $prefectures, null, ['class' => 'form-control select2', 'placeholder' => "選択してください"]) }}

                  </div>

                </div>
              </div>
            </div>

              <div class="row">
                <div class="col-sm-6">

                  <div class="form-group">

                    <label>支援活動中/支援終了</label>

                    <div class="form-check">

                      {{ Form::checkbox('include_disabled', '1', null, ['class' => 'form-check-input', 'id' => 'customCheckbox1']) }}
                      <label class="form-check-label" for="customCheckbox1">支援終了した支援団体も表示する</label>
                    </div>

                  </div>
                </div>

              </div>


            <div class="row">
              <div class="col-sm-6">

                <div class="form-group">

                  <label>支援種別</label>

                  <div class="form-group">

                    {{ Form::select('support_category2_id', $support_category2s, null, ['class' => 'form-control select2', 'placeholder' => "選択してください"]) }}

                  </div>

                </div>
              </div>

            </div>

          </div>
          <div class="card-footer">

            <button name="submit" class="btn btn-primary" value="submit"><i class="fas fa-search"></i> 検索</button>
          </div>

        </div>

          {!! Form::close() !!}

        </section>

      </div>


      <div class="row">


        <section class="col-lg-12 connectedSortable">

          <div class="card card-info">

            <!-- /.card-header -->
            <div class="card-body">

              <div class="row">
                <div class="col-sm-9">

                  {!! Form::model($condition, ['route' => ['organizations.index'], 'class' => 'form-horizontal', 'method' => 'GET']) !!}

                  <div class="form-inline">
                    <div class="form-group mr-2" style="width: 80%">
                        {{ Form::text('keyword', null, ['class' => 'form-control', 'placeholder' => "キーワードを入力してください", "style" => "width:100%"]) }}
                    </div>
                    <button name="submit" class="btn btn-primary" value="submit"><i class="fas fa-search"></i> 検索</button>

                  </div>


                  {!! Form::close() !!}

                </div>
                <div class="col-sm-3">
                  <div class="float-right">
                    <a href="{{ route('organizations.create') }}" class="btn btn-primary pull-right"><i class="fas fa-plus"></i> 支援団体を登録する</a>
                  </div>
                </div>
              </div>

              <br>

              @if(filled($organizations))
              <div class="scroll-table">
              <table class="table table-bordered">
                <thead>
                <tr>
                  <th style="width: 15em">名称</th>
                  <th>活動状況</th>
                  <th>都道府県</th>
                  <th>市区町村</th>
                  <th>操作</th>
                </tr>
                </thead>
                <tbody>

                @foreach($organizations as $organization)
                <tr>

                  <td>
                    <strong style="font-size: 1.1em">{{ $organization->name }}</strong>
                  </td>

                  <td>

                    @if ($organization->status)
                      <span class="badge badge-success">支援活動中</span>
                    @else
                      <span class="badge badge-dark">支援終了</span>
                    @endif

                  </td>

                  <td>
                    {{ $organization->localGovernment->prefecture->name }}
                  </td>

                  <td>{{ $organization->localGovernment->name }}</td>

                  <td>

                    <a href="{{ route('organizations.show', ['organization' => $organization->id]) }}" class="btn btn-primary"><i class="fas fa-eye"></i> 詳細</a>

                  </td>
                </tr>
                @endforeach


                </tbody>
              </table>
              </div>

              <br>

                {{ $organizations->links('vendor.pagination.bootstrap-4') }}


              @else
                <div class="alert alert-dark">
                  <div class="pT-1 pB-1">
                    <strong>登録がないか、条件に一致するデータがありません。</strong>

                  </div>
                </div>
              @endif

            </div>
            <!-- /.card-body -->

          </div>
        </section>
      </div>

    </div>
    <!-- END CONTAINER FLUID -->
  </div>
  <!-- END MAIN CONTENT -->

  </section>
@endsection
