@extends('layouts.app')

@section('content')


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">
              @if (Route::is("my_reports.index"))
                自団体の日報を探す
              @else
                支援団体日報を探す
              @endif

            </h1>
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

          {!! Form::model($condition, ['route' => [Route::currentRouteName()], 'id' => 'search_form' ,'class' => 'form-horizontal', 'method' => 'GET']) !!}

          <div class="card collapsed-card">
            <div class="card-header">
              <div class="card-tools">
                <button type="button" class="btn btn-sm btn-default" data-card-widget="collapse"><i class="fas fa-plus"></i> 詳細な条件欄の開閉</button>
              </div>
            </div>

            <div class="card-body">

              <div class="row">
                <div class="col-sm-12">

                  <div class="form-group">
                    <label>キーワード</label>

                    <div class="input-group">
                      {{ Form::text('keyword', null, ['class' => 'form-control']) }}
                    </div>
                    <small>全文を検索します</small>
                  </div>
                </div>

              </div>


                <div class="row">

                  {{-- 自団体検索の場合は非表示 --}}
                    <div class="col-sm-6" style="{{ Route::is("my_reports.index") ? "display:none" : "" }}">

                      <div class="form-group">
                        <label>支援団体</label>

                        {{ Form::select('organization_id', $organizations, null, ['class' => 'form-control select2', 'placeholder' => "選択してください", "style" => "width:100%;"]) }}

                      </div>
                    </div>



                    <div class="col-sm-6">

                      <div class="form-group">
                        <label>支援先</label>

                        {{ Form::select('shelter_id', $shelters, null, ['class' => 'form-control select2', 'placeholder' => "選択してください", "style" => "width:100%;"]) }}

                        <div class="form-check">

                          {{ Form::checkbox('include_disabled_shelters', 'true', null, ['class' => 'form-check-input', 'id' => 'include_disabled_shelters']) }}
                          <label class="form-check-label" for="include_disabled_shelters">支援終了した支援先も表示する</label>
                        </div>

                      </div>
                    </div>

                </div>


                <div class="row">

                  <div class="col-sm-6">
                    <div class="form-group">

                      <label>支援種別</label>

                      <div class="form-group">

                        {{ Form::select('support_category1_id', $support_category1_pulldown_list, null, ['class' => 'form-control select2', 'placeholder' => "選択してください"]) }}

                      </div>

                    </div>
                  </div>


                  <div class="col-sm-6">
                    <div class="form-group">

                      <label>状態</label>

                      <div class="form-group">

                        @foreach($signals as $signal => $label)
                        <div class="form-check">

                          <label class="form-check-label">

                            {{ Form::checkbox('signal_ids[]', $signal, null, ['class' => 'form-check-input']) }}

                            @if ($signal == '1')
                              <i class="nav-icon fas fa-flag text-info"></i>
                            @elseif ($signal == '2')
                              <i class="nav-icon fas fa-flag text-warning"></i>
                            @elseif ($signal == '3')
                              <i class="nav-icon fas fa-flag text-danger"></i>
                            @endif
                             {{ $label }}

                          </label>
                        </div>
                        @endforeach

                      </div>

                    </div>
                  </div>
                </div>


                <div class="row">
                  <div class="col-sm-6">

                    <div class="form-group">
                      <label>検索対象期間</label>

                      <div class="input-group">
                        {{ Form::text('start_date', null, ['class' => 'form-control datepicker']) }}
                        <div class="input-group-append">
                        <span class="input-group-text">
                        から
                        </span>
                        </div>
                      </div>

                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">

                      <label>　</label>

                      <div class="input-group">
                        {{ Form::text('end_date', null, ['class' => 'form-control datepicker']) }}
                        <div class="input-group-append">
                        <span class="input-group-text">
                          まで
                        </span>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>

              @if( ! Auth::user()->isAdmin())
              <div class="row">
                <div class="col-sm-6">

                  <div class="form-group">
                    <label>記入者</label>

                    <div class="input-group">
                      {{ Form::select('writer', $prev_writers, null, ['class' => 'form-control select2', 'placeholder' => "選択してください"]) }}
                    </div>

                  </div>
                </div>

              </div>
              @endif

              <div class="row">
                <div class="col">

                  <div class="form-group">
                    <label>タグ</label>

                    <div class="input-group">

                      {{ Form::text('tag_list', null, ['id' => "kt_tagify_6",'class' => 'form-control form-control-solid']) }}

                    </div>

                  </div>
                </div>
              </div>

            </div>

            <div class="card-footer">

              <button name="submit" id="submit_button" class="btn btn-primary" value="submit"><i class="fas fa-search"></i> 検索</button>
              <button name="submit" class="btn btn-primary" value="csv"><i class="fas fa-download"></i> CSV出力</button>
            </div>

          </div>

          </form>



          <div class="card card-info">

            <!-- /.card-header -->
            <div class="card-body">

              <div class="row">
                <div class="col-sm-9">

                  {!! Form::model($condition, ['route' => [Route::currentRouteName()], 'class' => 'form-horizontal', 'method' => 'GET']) !!}

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
                    <a href="{{ route('reports.create') }}" class="btn btn-primary pull-right"><i class="fas fa-plus"></i>  日々の活動内容を記録する</a>
                  </div>
                </div>
              </div>

              <br>

            @if(filled($reports))
              <div class="scroll-table">
              <table class="table table-bordered">
                <thead>
                <tr>
                  <th style="width: 120px;">支援日</th>
                  <th>
                  	@if (Route::is("my_reports.index"))
                    	記入者
                    @else
                      支援団体
                    @endif
                  </th>
                  <th>支援先</th>
                  <th>操作</th>
                </tr>
                </thead>
                <tbody>

                @foreach($reports as $report)
                <tr>
                  <td rowspan="2">{{ $report->report_date }}</td>

                  <td>
                    @if (Route::is("my_reports.index"))
                      {{ $report->writer }}
                    @else
                      {{ $report->organization->name }}
                    @endif

                    @if($report->updated_by_admin)
                      <br>
                      <span class="text-red">
                    （代信：<i class="fa fa-user"></i>{{ $report->updateUser->name }} {{ $report->updated_at->format('Y/m/d H:i') }}更新）
                    </span>
                    @endif

                  </td>
                  <td>{{ $report->shelter->name }}</td>
                  <td>

                    <a href="{{ route('reports.show', ['report' => $report->id]) }}" class="btn btn-primary"><i class="fas fa-eye"></i> 詳細</a>

                    @can('update', $report)
                    <a href="{{ route('reports.create', ['report_id' => $report->id]) }}" class="btn btn-primary"><i class="fas fa-file"></i> コピー</a>
                    @endcan
                  </td>
                </tr>
                <tr>
                  <td colspan="4">
                    @foreach($report->getSupportCategoryInfo() as $support_category_info)

                      <span>
                          @if ($support_category_info["signal"] == 1)
                          <i class="nav-icon fas fa-flag text-info"></i>
                        @elseif ($support_category_info["signal"] == 2)
                          <i class="nav-icon fas fa-flag text-warning"></i>
                        @elseif ($support_category_info["signal"] == 3)
                          <i class="nav-icon fas fa-flag text-danger"></i>
                        @elseif ($support_category_info["signal"] == \App\Signal::NO_SIGNAL)
                          <i class="nav-icon fas fa-comment"></i>
                        @endif
                        {{ $support_category_info["name"] }}
                      </span>

                      [
                      @foreach($report->getSubCategories($support_category_info["id"]) as $sub_category)

                        <span>
                          @if ($sub_category->pivot->signal == 1)
                            <i class="nav-icon fas fa-flag text-info"></i>
                          @elseif ($sub_category->pivot->signal == 2)
                            <i class="nav-icon fas fa-flag text-warning"></i>
                          @elseif ($sub_category->pivot->signal == 3)
                            <i class="nav-icon fas fa-flag text-danger"></i>
                          @elseif ($sub_category->pivot->signal == \App\Signal::NO_SIGNAL)
                            <i class="nav-icon fas fa-comment"></i>
                          @endif
                          {{ $sub_category->name }}

                          @if ( ! $loop->last)
                          ,
                          @endif

                      </span>

                      @endforeach
                    	]　

                    @endforeach

                  </td>
                </tr>
                @endforeach


                </tbody>
              </table>
              </div>

                <br>
                {{ $reports->links('vendor.pagination.bootstrap-4') }}


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

@section('script')

  <script>

    $(document).ready(function() {
      $('.select2').select2();
    });

    $('#include_disabled_shelters').on("change", function() {
      {{--
       submitボタンの名称がsubmitであるので通常の.submit()が実行できなかった。
       参考：
        http://dqn.sakusakutto.jp/2013/04/jquery_form_submit.html
       --}}
      $('#submit_button').trigger("click");
    })

  </script>
  <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />

  <script>
    var input = document.querySelector("#kt_tagify_6");

    // Initialize Tagify script on the above inputs
    new Tagify(input, {
      whitelist: @json($tags),
      dropdown: {
        maxItems: 20,           // <- mixumum allowed rendered suggestions
        classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
        enabled: 0,             // <- show suggestions on focus
        closeOnSelect: false    // <- do not hide the suggestions dropdown once an item has been selected
      }
    });
  </script>


@endsection
