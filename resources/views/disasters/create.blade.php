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
                新規作成
              </h3>
            </div>

          {!! Form::model($disaster, ['route' => ['disasters.store', $disaster->id], 'class' => 'form-horizontal']) !!}

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
                  年度
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
                  発生年月日
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

                <label for="local_government_ids" class="col-sm-2 col-form-label">
                  市町村
                </label>

                <div class="col-sm-10">

                  <select name="local_government_ids[]" id="local_government_ids" class="form-control select2" multiple>
                    @foreach($localGovernments->groupBy('prefecture_code') as $localGovernmentsByPrefecture)
                      <optgroup label="{{ $localGovernmentsByPrefecture->first()->prefecture->name }}">
                        @foreach($localGovernmentsByPrefecture as $localGovernment)
                          <option value="{{ $localGovernment->id }}" @selected(in_array($localGovernment->id, (array) $selectedLocalGovernmentIds))>
                            {{ $localGovernment->name }}
                          </option>
                        @endforeach
                      </optgroup>
                    @endforeach
                  </select>

                  <small class="form-text text-muted">
                    複数選択できます。地図は選択した市町村の行政区域に合わせて表示します。
                  </small>
                  {{ Form::inputError('local_government_ids') }}

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


              {{-- 最新情報リンク --}}
              <div class="form-group row">

                <label class="col-sm-2 col-form-label">
                  最新情報リンク
                </label>

                <div class="col-sm-10">

                  <div id="information-links">
                    @foreach ($informationLinks as $index => $informationLink)
                      <div class="information-link-row border rounded p-3 mb-2">
                        <div class="form-group">
                          <label>概要文</label>
                          <textarea name="information_links[{{ $index }}][description]" class="form-control" rows="2">{{ $informationLink['description'] ?? '' }}</textarea>
                          {{ Form::inputError("information_links.{$index}.description") }}
                        </div>
                        <div class="form-group mb-2">
                          <label>URL</label>
                          <input type="url" name="information_links[{{ $index }}][url]" class="form-control" value="{{ $informationLink['url'] ?? '' }}" placeholder="https://example.com/">
                          {{ Form::inputError("information_links.{$index}.url") }}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-information-link">削除</button>
                      </div>
                    @endforeach
                  </div>

                  <button type="button" class="btn btn-sm btn-outline-info" id="add-information-link">行を追加</button>
                  <small class="form-text text-muted">
                    概要文とURLの両方を入力してください。
                  </small>

                </div>
              </div>
              {{-- / 最新情報リンク --}}


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
  <script>
    (function () {
      let informationLinkIndex = {{ (int) collect($informationLinks)->keys()->max() + 1 }};
      const container = document.getElementById('information-links');
      const addButton = document.getElementById('add-information-link');

      if (!container || !addButton) {
        return;
      }

      addButton.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'information-link-row border rounded p-3 mb-2';
        row.innerHTML = '<div class="form-group">'
          + '<label>概要文</label>'
          + '<textarea name="information_links[' + informationLinkIndex + '][description]" class="form-control" rows="2"></textarea>'
          + '</div>'
          + '<div class="form-group mb-2">'
          + '<label>URL</label>'
          + '<input type="url" name="information_links[' + informationLinkIndex + '][url]" class="form-control" placeholder="https://example.com/">'
          + '</div>'
          + '<button type="button" class="btn btn-sm btn-outline-danger remove-information-link">削除</button>';

        container.appendChild(row);
        informationLinkIndex++;
      });

      container.addEventListener('click', function (event) {
        if (!event.target.classList.contains('remove-information-link')) {
          return;
        }

        event.target.closest('.information-link-row').remove();
      });
    })();
  </script>
@endsection
