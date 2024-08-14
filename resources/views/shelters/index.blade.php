@extends('layouts.app')

@section('content')


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">支援先</h1>
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

          {!! Form::model($condition, ['route' => ['shelters.index'], 'class' => 'form-horizontal', 'method' => 'GET']) !!}

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

                    {{ Form::select('prefecture_id', $prefectures, null, ['class' => 'select2 form-control', 'placeholder' => "選択してください", "style" => "width:100%;"]) }}

                  </div>

                </div>
              </div>
            </div>


            <div class="row">
              <div class="col-sm-6">

                <div class="form-group">

                  <label>有効/支援終了</label>

                  <div class="form-check">

                    {{ Form::checkbox('include_disabled', '1', null, ['class' => 'form-check-input', 'id' => 'customCheckbox1']) }}
                    <label class="form-check-label" for="customCheckbox1">支援終了した支援先も表示する</label>
                  </div>

                </div>
              </div>

            </div>

            <hr>

            <div class="row">

              <div class="col-sm-6">
                <div class="form-group">

                  <label>支援種別</label>

                  <div class="form-group">

                    {{ Form::select('support_category1_id', $support_category1_pulldown_list, null, ['class' => 'form-control', 'placeholder' => "選択してください"]) }}

                  </div>

                </div>
              </div>


              <div class="col-sm-6">
                <div class="form-group">

                  <label>状態</label>

                  <div class="input-group">

                    @foreach($signals as $signal => $label)
                      <div class="form-check">

                        <label class="form-check-label">

                          {{ Form::radio('signal_id', $signal, null, ['class' => 'signal_radio']) }}

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
                   　　<button type="button" class="signal_reset btn btn-default btn-sm">未選択に戻す</button>

                  </div>

                </div>
              </div>
            </div>


            <div class="row">
              <div class="col-sm-6">

                <div class="form-group">
                  <label>支援日</label>
                  <span class="small">※支援種別、状態を入力した場合のみ条件に追加されます。</span>

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

          </div>
          <div class="card-footer">

            <button name="submit" class="btn btn-primary" value="submit"><i class="fas fa-search"></i> 検索</button>
            <button name="submit" class="btn btn-primary" value="csv"><i class="fas fa-download"></i> CSV出力</button>
          </div>

        </div>

          </form>

        </section>

      </div>


      <div class="row">


        <section class="col-lg-12 connectedSortable">

          <div class="card card-info">

            <!-- /.card-header -->
            <div class="card-body">

              <div class="row">
                <div class="col-sm-9">

                  {!! Form::model($condition, ['route' => ['shelters.index'], 'class' => 'form-horizontal', 'method' => 'GET']) !!}

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
                    <a href="{{ route('shelters.create') }}" class="btn btn-primary pull-right"><i class="fas fa-plus"></i>  新規作成</a>
                  </div>
                </div>
              </div>

              <br>




            @if(filled($shelters))

              <div class="scroll-table">
              <table class="table table-bordered">
                <thead>
                <tr>
                  <th style="width: 15em">名称</th>
                  <th style="width: 15em">ステータス</th>
                  <th style="width: 8em">都道府県</th>
                  <th style="width: 10em">市区町村</th>
                  <th style="width: 400px">支援の状態</th>
                  <th style="width: 140px">操作</th>
                </tr>
                </thead>
                <tbody>

                @foreach($shelters as $shelter)
                <tr>


                  <td>
                    <strong style="font-size: 1.1em">
                      {{ $shelter->name }}
                    </strong>
                  </td>
                  <td>

                    @if ($shelter->status)
                      <span class="badge badge-success">有効</span>
                    @else
                      <span class="badge badge-dark">支援終了</span>
                    @endif

                  </td>

                  <td>
                    {{ $shelter->localGovernment->prefecture->name }}
                  </td>

                  <td>
                    {{ $shelter->localGovernment->name }}
                  </td>

                  <td>
                      @foreach ($shelter->category1_list as $category1)
                        @if ($category1['report_count'])

                        <a href="{{ route('reports.index', ["support_category1_id" => $category1["support_category1"]->id, "shelter_id" => $shelter->id, "start_date" => $condition->start_date, "end_date" => $condition->end_date]) }}">
                        <span>

                          @if ($category1["signal_id"] == \App\Signal::NO_SIGNAL)
                            <i class="nav-icon fas fa-comment text-{{ $category1["signal_css"] }}"></i>
                          @else
                            <i class="nav-icon fas fa-flag text-{{ $category1["signal_css"] }}"></i>
                          @endif

                          {{ $category1["support_category1"]->name }}
                          ({{ $category1['report_count'] }})

                        　</span>
                        </a>
                        @endif
                      @endforeach
                  </td>

                  <td>
                    <a href="{{ route('shelters.show', ['shelter' => $shelter->id]) }}" class="btn btn-primary"><i class="fas fa-eye"></i> 詳細</a>
                  </td>
                </tr>
                @endforeach


                </tbody>
              </table>
              </div>

              <br>
                {{ $shelters->links('vendor.pagination.bootstrap-4') }}


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

    $('.signal_reset').on("click", function() {
      $('.signal_radio').prop("checked", false);
    });

  </script>
@endsection
