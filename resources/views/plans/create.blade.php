@extends('layouts.app')

@section('content')

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">支援予定</h1>
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

    @if ($errors->any())
      <div class="alert alert-danger">
        入力内容に不備があります。ご確認ください。
      </div>
    @endif

    <!-- ROW -->
      <div class="row">

        <section class="col-lg-12 connectedSortable">

          <div class="card card-info">
            <div class="card-header">
              <h3 class="card-title">支援予定
                新規作成
              </h3>
            </div>

          {!! Form::model($report, ['route' => ['plans.store'], 'class' => 'form-horizontal']) !!}
            {{ Form::hidden('from_page',null) }}

            <!-- /.card-header -->
            <div class="card-body">

              <p>必要事項を入力し、画面下のボタンを押してください。</p>


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援団体
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  <div class="all-shelter input-group input-group-lg mb-3">
                    {{ Form::select('organization_id', $organizations, null, ['id' => '', 'class' => 'form-control shelter_select select2', 'placeholder' => '-- 支援団体を選択してください --']) }}
                  </div>

                  {{ Form::inputError('organization_id') }}
                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援先
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">


                  {{-- 全ての支援先のプルダウンとモーダルでの検索 --}}
                  <div class="all-shelter input-group input-group-lg mb-3">
                    {{ Form::select('shelter_id', $shelters, null, ['id' => 'all_shelter_select', 'class' => 'form-control shelter_select select2', 'placeholder' => '-- 支援先を選択してください --']) }}
                  </div>

                  {{ Form::inputError('shelter_id') }}
                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援開始日
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::text('from', null, ['class' => 'form-control datepicker']) }}
                  {{ Form::inputError('from') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援終了日
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::text('to', null, ['class' => 'form-control datepicker']) }}
                  {{ Form::inputError('to') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              <hr>


              <div class="form-group row">


                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援種別
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  @foreach($support_category1s as $support_category1)
                    <label style="margin-right: 6px">
                      {{ Form::radio('support_category1_id', $support_category1->id, null, ['class' => 'support_category1']) }} {{ $support_category1->name }}
                    </label>
                  @endforeach

                  {{ Form::inputError('support_category1_id') }}
                  {{ Form::inputError('supportCategory2s') }}


                  @foreach($support_category1s as $support_category1)

                    <div class="cat1 mt-2" data-category="{{ $support_category1->id }}" style="display: none">

                      @if (filled($support_category1->supportCategory2s))
                        @foreach($support_category1->supportCategory2s as $support_category2)
                          <span style="padding-right: 10px;">

                            <label style="display: inline">
                              {{ Form::checkbox('supportCategory2s[]', $support_category2->id, null, ['class' => 'support_category2_check','data-support-category1' => $support_category1->id, 'data-support-category2' => $support_category2->id]) }} {{ $support_category2->name }}
                            </label>

                            {{ Form::inputError('support_category_values['.$support_category2->id.'][signal]') }}

{{--                            {{ Form::textarea('support_category_values['.$support_category2->id.'][memo]', null, ['rows' => 3 ,'class' => 'form-control support_category2', 'placeholder' => 'メモを入力してください', 'data-support-category2' => $support_category2->id]) }}--}}

                          </span>
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

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援内容
                </label>

                <div class="col-sm-10">

                  {{ Form::textarea('description', null, ["id" => "comment", 'class' => 'form-control', "rows" => 15]) }}
                  {{ Form::inputError('description') }}

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

  <!-- END CONTAINER FLUID -->
</div>
<!-- END MAIN CONTENT -->

  </section>

@endsection

@section('script')

  <script>

    /*
     * 画面表示時
     */
    $(document).ready(function() {

      changeSupportCategory1();
      changeSupportCategory2();

      $('.select2').select2();
    });

    /**
     * 支援種別(大)のチェックによる、エリアの開閉制御
     */
    var changeSupportCategory1 = function() {

      $('.support_category1').each(function(index, supportCategory1Checkbox) {

        var isChecked          = $(supportCategory1Checkbox).prop('checked');
        var supportCategory1Id = $(supportCategory1Checkbox).val();

        // 支援種別(中)エリアを発火する。
        $(".cat1[data-category=" + supportCategory1Id + "]").toggle(isChecked);


        var target = $(".support_category2_check[data-support-category1='"+supportCategory1Id+"']");

        // 支援種別(大)のチェックを外した場合は、支援種別(中)のチェックを外し、イベントを発火
        if ( ! isChecked) {
          target.prop("checked", false).trigger('change');
        }
      })
    }

    /**
     * 支援種別(中)選択時のチェック、入力欄の有効化
     */
    var changeSupportCategory2 = function() {

      $('.support_category2_check').each(function(index, supportCategory2Checkbox) {

        var isChecked = $(supportCategory2Checkbox).prop('checked');
        var supportCategory2Id = $(supportCategory2Checkbox).data('supportCategory2');

        var targetRadio = $("input[type='radio'].support_category2[data-support-category2='"+supportCategory2Id+"']");
        var targetText = $("input[type='text'].support_category2[data-support-category2='"+supportCategory2Id+"']");

        targetRadio.prop('disabled', ( ! isChecked))
        targetText.prop('disabled', ( ! isChecked))

        if ( ! isChecked) {
          targetText.val("");
          targetRadio.prop("checked", false);
        }
      })
    }

    $('.support_category2_check').on('change', function() {
      changeSupportCategory2()
    })


    /**
     * 支援先状況の開閉
     */
    $('.support_category1').on('change', function() {
      changeSupportCategory1();
    })




  </script>


@endsection
