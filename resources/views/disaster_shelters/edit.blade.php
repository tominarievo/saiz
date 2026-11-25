@extends('layouts.app')

@section('content')

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">支援先の紐付け</h1>
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

        <div class="card card-info">
          <div class="card-header">
            <h3 class="card-title">支援先の紐付け</h3>
          </div>

          <!-- /.card-header -->
          <div class="card-body">

            <p>名称・所在地で検索し、右の「未登録」から左の「登録済み」へ追加してください。</p>

            <div class="form-group row">
              <label class="col-sm-2 col-form-label">災害情報</label>
              <div class="col-sm-10">
                {{ $disaster->name }}
              </div>
            </div>

            {{-- 検索 --}}
            {!! Form::open(['route' => ['disaster_shelters.edit', ['disaster_id' => $disaster->id]], 'method' => 'GET', 'class' => 'form-horizontal js-search-form']) !!}

            <div class="row">

              <div class="col-md-4">
                <div class="form-group">
                  <label>名称</label>
                  {{ Form::text('name', request('name'), ['class' => 'form-control', 'placeholder' => '名称・名称かな']) }}
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label>所在地</label>
                  {{ Form::text('address', request('address'), ['class' => 'form-control', 'placeholder' => '都道府県・市区町村・住所']) }}
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label>&nbsp;</label>
                  <div>
                    <button type="submit" class="btn btn-info"><i class="fas fa-search"></i> 検索</button>
                    <a href="{{ route('disaster_shelters.edit', ['disaster_id' => $disaster->id]) }}" class="btn btn-default js-search-link">クリア</a>
                  </div>
                </div>
              </div>

            </div>

            {!! Form::close() !!}

          </div>
        </div>

        {!! Form::model($disaster, ['route' => ['disaster_shelters.update', ['disaster_id' => $disaster->id]], 'class' => 'form-horizontal js-link-form']) !!}
        @method('PUT')

        <div class="row">

          {{-- 登録済み --}}
          <div class="col-md-6">
            <div class="card card-success">
              <div class="card-header">
                <h3 class="card-title">
                  登録済み
                  <span class="badge badge-light js-registered-count">{{ $registered->count() }}</span>
                </h3>
              </div>
              <div class="card-body p-0">
                <ul class="list-group list-group-flush js-registered-list" data-empty="登録済みの支援先はありません">
                  @foreach($registered as $shelter)
                    @php
                      $shelterAddress = trim(implode(' ', array_filter([
                        $shelter->npo_col_11,
                        $shelter->npo_col_12,
                        $shelter->npo_col_3,
                        $shelter->npo_col_4,
                      ])));
                    @endphp
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $shelter->id }}">
                      <div>
                        <input type="hidden" name="shelters[]" value="{{ $shelter->id }}">
                        <div class="font-weight-bold">{{ $shelter->name }}</div>
                        @if($shelterAddress !== '')
                          <div class="text-muted small">{{ $shelterAddress }}</div>
                        @endif
                      </div>
                      <button type="button" class="btn btn-sm btn-outline-danger js-unlink">
                        <i class="fas fa-arrow-left"></i> 解除
                      </button>
                    </li>
                  @endforeach
                </ul>
              </div>
            </div>
          </div>

          {{-- 未登録 --}}
          <div class="col-md-6">
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">
                  未登録
                  <span class="badge badge-light js-candidate-count">{{ $candidates->total() }}</span>
                </h3>
              </div>
              <div class="card-body p-0">
                <ul class="list-group list-group-flush js-candidate-list" data-empty="該当する未登録の支援先はありません">
                  @foreach($candidates as $shelter)
                    @php
                      $shelterAddress = trim(implode(' ', array_filter([
                        $shelter->npo_col_11,
                        $shelter->npo_col_12,
                        $shelter->npo_col_3,
                        $shelter->npo_col_4,
                      ])));
                    @endphp
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $shelter->id }}">
                      <div>
                        <div class="font-weight-bold">{{ $shelter->name }}</div>
                        @if($shelterAddress !== '')
                          <div class="text-muted small">{{ $shelterAddress }}</div>
                        @endif
                      </div>
                      <button type="button" class="btn btn-sm btn-outline-info js-link">
                        <i class="fas fa-arrow-right"></i> 追加
                      </button>
                    </li>
                  @endforeach
                </ul>
              </div>
              <div class="card-footer">
                {{ $candidates->links('vendor.pagination.bootstrap-4') }}
              </div>
            </div>
          </div>

        </div>

        <div class="card">
          <div class="card-footer">
            <button name="submit" class="btn btn-info" value="submit">登録</button>
            <a href="{{ route('disasters.index') }}" class="btn btn-default">戻る</a>
          </div>
        </div>

        {!! Form::close() !!}

      </div>
      <!-- END CONTAINER FLUID -->
    </section>
    <!-- END MAIN CONTENT -->

  </div>
  <!-- END CONTENT WRAPPER -->
@endsection

@section('script')
<script>
  $(function () {
    var $registered = $('.js-registered-list');
    var $candidates = $('.js-candidate-list');
    var dirty = false;

    function refreshCounts() {
      $('.js-registered-count').text($registered.children('li').not('.js-empty').length);
      $('.js-candidate-count').text($candidates.children('li').not('.js-empty').length);
    }

    function refreshEmpty() {
      [$registered, $candidates].forEach(function ($list) {
        var $empty = $list.children('.js-empty');
        var hasItems = $list.children('li').not('.js-empty').length > 0;

        if (hasItems) {
          $empty.remove();
        } else if (!$empty.length) {
          $('<li class="list-group-item text-muted js-empty"></li>')
            .text($list.data('empty'))
            .appendTo($list);
        }
      });

      refreshCounts();
    }

    function moveToCandidates($li) {
      $li.find('input[name="shelters[]"]').remove();
      $li.find('button')
        .removeClass('js-unlink btn-outline-danger')
        .addClass('js-link btn-outline-info')
        .html('<i class="fas fa-arrow-right"></i> 追加');
      $candidates.append($li);
    }

    function moveToRegistered($li) {
      $li.prepend(
        $('<input type="hidden" name="shelters[]">').attr('value', $li.data('id'))
      );
      $li.find('button')
        .removeClass('js-link btn-outline-info')
        .addClass('js-unlink btn-outline-danger')
        .html('<i class="fas fa-arrow-left"></i> 解除');
      $registered.append($li);
    }

    $registered.on('click', '.js-unlink', function () {
      moveToCandidates($(this).closest('li'));
      dirty = true;
      refreshEmpty();
    });

    $candidates.on('click', '.js-link', function () {
      moveToRegistered($(this).closest('li'));
      dirty = true;
      refreshEmpty();
    });

    function confirmDiscard(e) {
      if (dirty && !confirm('未保存の変更は失われます。よろしいですか？')) {
        e.preventDefault();
      }
    }

    $('.js-search-form').on('submit', confirmDiscard);
    $(document).on('click', '.js-search-link, .pagination a', confirmDiscard);

    refreshEmpty();
  });
</script>
@endsection
