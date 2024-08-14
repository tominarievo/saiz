@extends('layouts.app')

@section('content')


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">お知らせ</h1>
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

          {!! Form::model($condition, ['route' => [Route::currentRouteName()], 'class' => 'form-horizontal', 'method' => 'GET']) !!}

          <div class="card card-info">
          <div class="card-header">
            <h3 class="card-title">検索条件</h3>

            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i>
              </button>
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
            </div>

          </div>
          <div class="card-footer">

            <button name="submit" class="btn btn-primary" value="submit"><i class="fas fa-search"></i> 検索</button>
          </div>

        </div>

          </form>

        </section>

      </div>


      <div class="row">


        <section class="col-lg-12 connectedSortable">

          <div class="card card-info">

            <div class="card-header">
              <h3 class="card-title">一覧</h3>
            </div>

            <!-- /.card-header -->
            <div class="card-body">

              <a href="{{ route('information.create') }}" class="btn btn-primary pull-right"><i class="fas fa-plus"></i>  新規作成</a>
              <br><br>

              @if(filled($list))
              <div class="scroll-table">
              <table class="table table-bordered">
                <thead>
                <tr>
                  <th>公開/非公開</th>
                  <th style="">公開日時</th>
                  <th style="">タイトル</th>
                  <th>操作</th>
                </tr>
                </thead>
                <tbody>

                @foreach($list as $entity)
                <tr>
                  <td>
                    <strong style="font-size: 1.1em">

                      @if ($entity->status)
                        <span class="badge badge-success">公開</span>
                      @else
                        <span class="badge badge-dark">非公開</span>
                      @endif

                    </strong>
                  </td>
                  <td>
                    {{ $entity->published_at->format('Y/m/d') }}
                  </td>
                  <td>
                    {{ \Illuminate\Support\Str::limit($entity->title, 40) }}
                  </td>
                  <td>
                    <a href="{{ route('information.edit', ['information' => $entity->id]) }}" class="btn btn-primary"><i class="fas fa-edit"></i> 編集</a>

                    {{--                    @can('delete', $user)--}}
                    {!! Form::open(['route' => ['information.destroy', $entity->id], 'style' => 'display:inline']) !!}
                    @method('DELETE')
                    <button class="btn btn-danger" onclick="if ( ! confirm('削除します。よろしいですか？')){return false;}"><i class="fas fa-trash"></i> 削除</button>
                    {!! Form::close() !!}
                    {{--                    @endcan--}}

                  </td>
                </tr>
                @endforeach


                </tbody>
              </table>
              </div>

              <br>

                {{ $list->links('vendor.pagination.bootstrap-4') }}


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

@endsection
