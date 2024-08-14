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
      <section class="row">

        <section class="col-lg-12 connectedSortable">

          <div class="card card-info">
            <div class="card-header">
              <h3 class="card-title">基本情報
              </h3>
            </div>

            <!-- /.card-header -->
            <div class="card-body">

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  名称
                </label>

                <div class="col-sm-10">

                  {{ $organization->name }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  ステータス
                </label>

                <div class="col-sm-10">

                  @if ($organization->status)
                    <span class="badge badge-success">有効</span>
                  @else
                    <span class="badge badge-dark">無効</span>
                  @endif

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  代表者
                </label>

                <div class="col-sm-10">

                  {{ $organization->npo_col_2 }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  住所
                </label>

                <div class="col-sm-10">

                  {{ $organization->localGovernment->prefecture->name }}
                  {{ $organization->localGovernment->name }}
                  {{ $organization->npo_col_3 }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  電話番号
                </label>

                <div class="col-sm-10">


                  {{ $organization->npo_col_4 }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  公開用メモ
                </label>

                <div class="col-sm-10">

                  {!! \App\UtilLogic::getEditedContent($organization->description) !!}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  備考
                </label>

                <div class="col-sm-10">

                  {!! \App\UtilLogic::getEditedContent($organization->npo_col_5) !!}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              <hr>

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  ユーザー
                </label>

                <div class="col-sm-10">

                  @can('update', $organization)

                    @if(filled($organization->users))
                      <a href="{{ route('users.edit', ['user' => $organization->users->first()->id]) }}" class="btn btn-default pull-right"><i class="fas fa-edit"></i>  ユーザーを編集する</a>

                      {!! Form::open(['route' => ['users.destroy', $organization->users->first()->id], 'style' => 'display:inline']) !!}
                      @method('DELETE')
                      <button class="btn btn-danger" onclick="if ( ! confirm('削除します。よろしいですか？')){return false;}"><i class="fas fa-trash"></i> ユーザーを削除する</button>
                      {!! Form::close() !!}

                    @else
                      <a href="{{ route('users.create', ['organization_id' => $organization->id]) }}" class="btn btn-primary pull-right"><i class="fas fa-plus"></i>  ユーザーを作成する</a>
                    @endif

                  @endcan

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

                  {!! \App\UtilLogic::getEditedContent($organization->npo_col_6) !!}

                </div>
              </div>
              {{-- / 1入力項目 --}}
              @endcan

            </div>



            <div class="card-footer">

              @can('update', $organization)
              <a href="{{ route('organizations.edit', ['organization' => $organization->id]) }}" class="btn btn-primary"><i class="fas fa-edit"></i> 編集</a>
              @endcan

              @can('delete', $organization)
              {!! Form::open(['route' => ['organizations.destroy', $organization->id], 'style' => 'display:inline']) !!}
              @method('DELETE')
              <button class="btn btn-danger" onclick="if ( ! confirm('削除します。よろしいですか？')){return false;}"><i class="fas fa-trash"></i> 削除</button>
              @endcan

                {!! Form::close() !!}

            </div>


            <!-- END BASIC TABLE -->
        </div>
        </section>
      <!-- END ROW -->

      <section class="col-lg-12 connectedSortable">

        <div class="card card-info">
          <div class="card-header">
            <h3 class="card-title">支援種別
            </h3>
          </div>

          <!-- /.card-header -->
          <div class="card-body">

            <table class="table table-bordered">
              <thead>
              <tr>
                <th>支援種別</th>
                <th>コメント</th>
                <th>操作</th>
              </tr>
              </thead>
              <tbody>

              @foreach($organization->seeds as $seed)

                <tr>
                  <td>{{ $seed->supportCategory1->name }} - {{ $seed->name }}</td>
                  <td>{!! nl2br(e($seed->pivot->comment)) !!}</td>
                  <td>
                    @can('update', $organization)
                    <a href="{{ route("organization_seeds.edit", ['organization_seed' => $seed->pivot->id]) }}" disabled class="btn btn-primary pull-right"><i class="fas fa-edit"></i> コメントを編集する</a>
                    @endcan

                    @can('delete', $organization)
                    {!! Form::open(['route' => ['organization_seeds.destroy', $seed->pivot->id], 'style' => 'display:inline']) !!}
                    @method('DELETE')
                    <button class="btn btn-danger" onclick="if ( ! confirm('削除します。よろしいですか？')){return false;}"><i class="fas fa-trash"></i> 削除する</button>
                    {!! Form::close() !!}
                    @endcan

                  </td>
                </tr>

              @endforeach


              </tbody>
            </table>


          </div>



          <div class="card-footer">

            @can('update', $organization)
            <a href="{{ route('organization_seeds.create', ['organization_id' => $organization->id]) }}" class="btn btn-primary"><i class="fas fa-plus"></i> 追加</a>
            @endcan

          </div>

          <!-- END BASIC TABLE -->
        </div>
      </section>
      <!-- END ROW -->




  </div>
  <!-- END CONTAINER FLUID -->
</div>
<!-- END MAIN CONTENT -->

  </section>
@endsection
