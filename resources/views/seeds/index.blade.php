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


        <section class="col-lg-12 connectedSortable">

          {!! Form::model($condition, ['route' => ['seeds.index'], 'class' => 'form-horizontal', 'method' => 'GET']) !!}

          <div class="card card-info collapsed-card">
            <div class="card-header">
              <h3 class="card-title">検索条件</h3>

              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i>
                </button>
              </div>
            </div>

            <div class="card-body">
                <div class="row">

                  <div class="col-sm-12">

                    <div class="form-group">
                      <label>支援団体</label>

                      {{ Form::select('organization_id', $organizations, null, ['class' => 'form-control select2', 'placeholder' => "選択してください"]) }}

                    </div>
                  </div>

                </div>


                <div class="row">
                  <div class="col-sm-6">

                    <div class="form-group">
                      <label>キーワード</label>

                      <div class="input-group">
                        {{ Form::text('keyword', null, ['class' => 'form-control']) }}
                      </div>
                      <small>コメントを検索します</small>

                    </div>
                  </div>

                  <div class="col-sm-6">
                    <div class="form-group">

                      <label>都道府県</label>

                      <div class="form-group">

                        {{ Form::select('prefecture_id', $prefectures, null, ['class' => 'form-control', 'placeholder' => "選択してください"]) }}

                      </div>

                    </div>
                  </div>


                </div>


              <div class="row">
                <div class="col-sm-6">

                  <div class="form-group">

                    <label>支援種別</label>

                    <div class="form-group">

                      {{ Form::select('support_category2_id', $support_category2s, null, ['class' => 'form-control', 'placeholder' => "選択してください"]) }}

                    </div>

                  </div>
                </div>

              </div>

            </div>

            <div class="card-footer">

              <button name="submit" class="btn btn-primary" value="submit"><i class="fas fa-search"></i> 検索</button>
              <button name="submit" class="btn btn-primary" value="csv"><i class="fas fa-download"></i> CSV出力</button>
            </div>

            </form>

          </div>





          <div class="card card-info">

            <div class="card-header">
              <h3 class="card-title">一覧</h3>
            </div>

            <!-- /.card-header -->
            <div class="card-body">

              @if(filled($seeds))
              <table class="table table-bordered">
                <thead>
                <tr>
                  <th style="width: 15em">支援団体</th>
                  <th>支援種別(大)</th>
                  <th>支援種別</th>
                  <th>コメント</th>
                </tr>
                </thead>
                <tbody>

                @foreach($seeds as $user)
                <tr>
                  <td><strong style="font-size: 1.1em">{{ $user->organization->name }}</strong></td>
                  <td>{{ $user->supportCategory1->name }}</td>
                  <td>{{ $user->supportCategory2->name }}</td>
                  <td>{{ $user->comment }}</td>
                </tr>
                @endforeach

                </tbody>
              </table>


                <br>
                {{ $seeds->links('vendor.pagination.bootstrap-4') }}


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

          </form>

        </section>
      </div>

    </div>
    <!-- END CONTAINER FLUID -->
  </div>
  <!-- END MAIN CONTENT -->

  </section>
@endsection

@section('script')

@endsection
