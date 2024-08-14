@extends('layouts.app')

@section('content')

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-1">
            <a href="{{ url()->previous() }}" class="btn btn-default"><i class="fas fa-arrow-left"></i> 戻る</a>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <h1 class="m-0">支援団体日報</h1>
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

            <!-- /.card-header -->
            <div class="card-body">

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援団体
                </label>

                <div class="col-sm-10">

                    {{ $report->organization->name }}

                  @if($report->updated_by_admin)
                    <span class="text-red">
                    （代信：<i class="fa fa-user"></i>{{ $report->updateUser->name }} {{ $report->updated_at->format('Y/m/d H:i') }}更新）
                    </span>
                  @endif

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  災害情報
                </label>

                <div class="col-sm-10">

                  {{ $report->disaster->name }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援先
                </label>

                <div class="col-sm-10">

                    {{ $report->shelter->name }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援日
                </label>

                <div class="col-sm-10">

                  {{ $report->report_date }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  記入者
                </label>

                <div class="col-sm-10">

                    {{ $report->writer }}

                </div>
              </div>
              {{-- / 1入力項目 --}}



              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  タグ
                </label>

                <div class="col-sm-10">

                  @if (filled($report->tag_list))
                    <p>
                      @foreach(explode(',', $report->tag_list) as $tag)
                        <span class="badge badge-light add-tag-button" data-tag="{{ $tag }}">{{ $tag }}</span>
                      @endforeach
                    </p>
                  @endif

                </div>
              </div>
              {{-- / 1入力項目 --}}


              <hr>



              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  情報共有会議用メモ
                </label>

                <div class="col-sm-10">

                  {!! \App\UtilLogic::getEditedContent($report->comment) !!}

                </div>
              </div>
              {{-- / 1入力項目 --}}





              <div class="form-group row">


                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援先状況
                </label>

                <div class="col-sm-10">

                @foreach($support_category1s as $el)

                  <div class="cat1 mt-4" data-category="{{ $el["support_category1"]->id }}">
                  <h3>{{ $el["support_category1"]->name }}</h3>

                  @if (filled($el["support2_list"]))
                    @foreach($el["support2_list"] as $support_category2)
                      <div style="display: block; margin-left: 1.5em">

                        @if ($support_category2->pivot->signal == 1)
                          <i class="nav-icon fas fa-flag text-info"></i>
                        @elseif ($support_category2->pivot->signal == 2)
                          <i class="nav-icon fas fa-flag text-warning"></i>
                        @elseif ($support_category2->pivot->signal == 3)
                          <i class="nav-icon fas fa-flag text-danger"></i>
                        @elseif ($support_category2->pivot->signal == \App\Signal::NO_SIGNAL)
                          <i class="nav-icon fas fa-comment"></i>
                        @endif

                        {{ $support_category2->name }}

                        @if (filled($support_category2->pivot->memo))
                            <br>
                        {!! \App\UtilLogic::getEditedContent($support_category2->pivot->memo) !!}
                            <br>
                        @endif

                      </div>
                      <br>
                    @endforeach

                  @else
                      <div class="alert alert-info">
                        <div class="pT-1 pB-1">
                          <strong>支援種別(中)の登録がありません。</strong>

                        </div>
                      </div>
                  @endif

                  </div>

                @endforeach

              </div>

              </div>

              <hr>

              @if (filled(\Auth::user()->organization_id) && (\Auth::user()->organization_id == $report->organization_id))
              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  内部の申し送りメモ<span class="right badge badge-dark">非公開</span>
                </label>

                <div class="col-sm-10">

                  {!! \App\UtilLogic::getEditedContent($report->hidden_comment) !!}

                </div>
              </div>
              {{-- / 1入力項目 --}}
              @endif

            </div>

            <div class="card-footer">

              @can('update', $report)
                <a href="{{ route('reports.create', ['report_id' => $report->id]) }}" class="btn btn-primary"><i class="fas fa-file"></i> コピー</a>
              @endcan

              @can('update', $report)
                <a href="{{ route('reports.edit', ['report' => $report->id]) }}" class="btn btn-primary"><i class="fas fa-edit"></i> 編集</a>
              @endcan

              @can('delete', $report)
                {!! Form::open(['route' => ['reports.destroy', $report->id], 'style' => 'display:inline']) !!}
                @method('DELETE')
                <button class="btn btn-danger" onclick="if ( ! confirm('削除します。よろしいですか？')){return false;}"><i class="fas fa-trash"></i> 削除</button>
                {!! Form::close() !!}
              @endcan



            </div>

            {!! Form::close() !!}

            <!-- END BASIC TABLE -->
        </div>
      <!-- END ROW -->

        </section>
  <!-- END CONTAINER FLUID -->
</div>
<!-- END MAIN CONTENT -->

      </div>

@endsection

@section('script')


@endsection
