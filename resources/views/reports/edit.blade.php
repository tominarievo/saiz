@extends('layouts.app')

@section('content')

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
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

    @if ($errors->any())
      <div class="alert alert-danger">
        入力内容に不備があります。ご確認ください。
      </div>
    @endif

    <!-- ROW -->
      <div class="row">

        <section class="col-lg-12 connectedSortable">

          <div class="card card-info">

          {!! Form::model($report, ['route' => ['reports.update', $report->id], 'class' => 'form-horizontal', ]) !!}
          @method('PUT')

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

                  @if (filled(\Auth::user()->organization_id))

                    {{ \Auth::user()->organization->name }}
                    {{ Form::hidden('organization_id', null) }}

                  @else

                    {{ Form::select('organization_id', $organizations, null, ['class' => 'form-control select2']) }}

                  @endif

                  {{ Form::inputError('organization_id') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  災害情報
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::select('disaster_id', $disasters, null, ['class' => 'form-control select2', 'placeholder' => "選択してください"]) }}

                  {{ Form::inputError('disaster_id') }}

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

                    {{ Form::select('shelter_id', $shelters, null, ['id' => 'all_shelter_select', 'class' => 'form-control', 'placeholder' => '-- 支援先を選択してください --']) }}

                    <div class="input-group-append">
                      <button type="button" class="btn btn-default" data-toggle="modal" data-target="#shelterModal"><i class="fas fa-search"></i> 検索</button>
                    </div>
                    <div class="input-group-append">
                      <button type="button" class="btn btn-default" data-toggle="modal" data-target="#shelterMapModal"><i class="fas fa-map-marker-alt"></i> 地図検索</button>
                    </div>
                  </div>

                  {{-- 過去に入力した支援先 --}}
                  {{ Form::select('shelter_id', $prev_shelters, null, ['id' => 'prev_shelter_select', 'class' => 'form-control select2', 'placeholder' => '-- 支援先を選択してください --']) }}


                  {{ Form::inputError('shelter_id') }}

                  <label>{{ Form::checkbox('use_prev_shelter', 1, null, ['id' => 'prev_shelter_check', 'class' => '']) }} 過去に入力した支援先のみを選択肢に表示する</label>

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援日
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::text('report_date', null, ['class' => 'form-control datepicker']) }}
                  {{ Form::inputError('report_date') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  記入者
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  @if (filled($prev_writers))

                    {{ Form::select('prev_writer', $prev_writers, null, ['id' => 'prev_writer_select', 'class' => 'form-control select2', 'placeholder' => '-- 記入者を選択してください --']) }}


                    <label>{{ Form::checkbox('use_prev_writer', 1, null, ['id' => 'prev_writer_check', 'class' => '']) }}過去に入力した記入者から選択する</label>

                  @endif

                  {{ Form::text('writer', "", ['id' => 'writer_input', 'class' => 'form-control', 'placeholder' => '記入者名を入力してください']) }}
                  <small>※新しく記入者を入力する場合はこちらに入力してください</small>


                  {{ Form::inputError('prev_writer') }}
                  {{ Form::inputError('writer') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}



              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  タグ
                </label>

                <div class="col-sm-10">

                  {{ Form::text('tag_list', null, ['class' => 'tagify,form-control']) }}
                  {{ Form::inputError('tag_list') }}

                  <p>
                    @foreach($tags as $tag)
                      <span class="badge badge-light add-tag-button" data-tag="{{ $tag }}">{{ $tag }}</span>
                    @endforeach
                  </p>

                </div>
              </div>
              {{-- / 1入力項目 --}}


              <hr>


              <div class="form-group row">


                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  支援内容
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  @foreach($support_category1s as $support_category1)
                    <label style="margin-right: 6px">
                      {{ Form::checkbox('support_category1_ids[]', $support_category1->id, null, ['class' => 'support_category1']) }} {{ $support_category1->name }}
                    </label>
                  @endforeach

                  {{ Form::inputError('support_category1_ids') }}
                  {{ Form::inputError('supportCategory2s') }}


                  @foreach($support_category1s as $support_category1)

                    <div class="cat1 mt-4" data-category="{{ $support_category1->id }}" style="display: none">
                      <h3>{{ $support_category1->name }}</h3>

                      @if (filled($support_category1->supportCategory2s))
                        @foreach($support_category1->supportCategory2s as $support_category2)
                          <div style="display: block; margin-left: 1.5em">

                            <label style="margin-right: 20px">
                              {{ Form::checkbox('supportCategory2s[]', $support_category2->id, null, ['class' => 'support_category2_check','data-support-category1' => $support_category1->id, 'data-support-category2' => $support_category2->id]) }} {{ $support_category2->name }}
                            </label>
                            <br>

                            <label style="margin-right: 16px">{{ Form::radio('support_category_values['.$support_category2->id.'][signal]', 1, null, ['class' => 'support_category2', 'data-support-category2' => $support_category2->id]) }} <i class="nav-icon fas fa-flag text-info"></i>OK </label>

                            <label style="margin-right: 16px">{{ Form::radio('support_category_values['.$support_category2->id.'][signal]', 2, null, ['class' => 'support_category2', 'data-support-category2' => $support_category2->id]) }} <i class="nav-icon fas fa-flag text-warning"></i>一部課題あり </label>

                            <label style="margin-right: 16px">{{ Form::radio('support_category_values['.$support_category2->id.'][signal]', 3, null, ['class' => 'support_category2', 'data-support-category2' => $support_category2->id]) }} <i class="nav-icon fas fa-flag text-danger"></i> 非常に課題あり </label>

                            <label style="margin-right: 16px">{{ Form::radio('support_category_values['.$support_category2->id.'][signal]', \App\Signal::NO_SIGNAL, null, ['class' => 'support_category2', 'data-support-category2' => $support_category2->id]) }} <i class="nav-icon fas fa-comment"></i> メモ </label>

                            {{ Form::inputError('support_category_values['.$support_category2->id.'][signal]') }}

                            {{ Form::textarea('support_category_values['.$support_category2->id.'][memo]', null, ['rows' => 3 ,'class' => 'form-control support_category2', 'placeholder' => 'メモを入力してください', 'data-support-category2' => $support_category2->id]) }}


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

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  情報共有会議用メモ
                </label>

                <div class="col-sm-10">

                  {{ Form::textarea('comment', null, ["id" => "comment", 'class' => 'form-control', "rows" => 15]) }}
                  {{ Form::inputError('comment') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              <hr>

              @if (filled(\Auth::user()->organization_id))
              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  内部の申し送りメモ<span class="right badge badge-dark">非公開</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::textarea('hidden_comment', null, ['class' => 'form-control']) }}
                  {{ Form::inputError('hidden_comment') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}
              @endif

            </div>

            <div class="card-footer">

              <button name="submit" class="btn btn-primary" value="submit">登録</button>
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

    @component("components.shelter_search_modal")@endcomponent
    @component("components.shelter_search_map_modal")@endcomponent



@endsection

@section('script')

  <style>
    .tagify{
      width: 100%;
      max-width: 700px;
    }
  </style>

  <script>

    $(document).ready(function() {
      changePrevShelter();
      changePrevWriter();

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

    /**
     * 支援先の選択プルダウンを切り替える
     */
    var changePrevShelter = function() {

      var isChecked = $('#prev_shelter_check').prop('checked');

      if (isChecked) {

        $('.all-shelter').hide();

        $('#all_shelter_select').hide().prop('disabled', true);
        $('#prev_shelter_select').show().prop('disabled', false);

      } else {

        $('.all-shelter').show();

        $('#all_shelter_select').show().prop('disabled', false);
        $('#prev_shelter_select').hide().prop('disabled', true);
      }
    }

    var changePrevWriter = function() {

      var isChecked = $('#prev_writer_check').prop('checked');

      console.log(isChecked);

      if (isChecked) {

        // 入力欄を空にする
        $('#writer_input').val('');

        $('#writer_input').prop('disabled', true);
        $('#prev_writer_select').prop('disabled', false);

      } else {

        // 選択を空にする
        $('#prev_writer_select').val('');

        $('#writer_input').prop('disabled', false);
        $('#prev_writer_select').prop('disabled', true);
      }
    }

    $('#prev_shelter_check').on('change', function() {
      changePrevShelter();
    })

    $('#prev_writer_check').on('change', function() {
      changePrevWriter();
    })


  </script>

      {{--  タグ  --}}

      <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
      <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />

      <script>
        var input = document.querySelector('input[name=tag_list]');
            // init Tagify script on the above inputs
        var tagify = new Tagify(input, {
          whitelist : @json($tags),
          blacklist : []
        });

        // "remove all tags" button event listener
        // document.querySelector('.tags--removeAllBtn')
        // .addEventListener('click', tagify.removeAllTags.bind(tagify))

        // Chainable event listeners
        tagify.on('add', onAddTag)
        .on('remove', onRemoveTag)
        .on('invalid', onInvalidTag);

        // tag added callback
        function onAddTag(e){
          console.log(e, e.detail);
          console.log( tagify.DOM.originalInput.value )
          tagify.off('add', onAddTag) // exmaple of removing a custom Tagify event
        }

        // tag remvoed callback
        function onRemoveTag(e){
          console.log(e, e.detail);
        }

        // invalid tag added callback
        function onInvalidTag(e){
          console.log(e, e.detail);
        }

        // 手動のタグ追加
        $('.add-tag-button').on('click', function(){
          const clickedTag = $(this).data('tag');
          tagify.addTags(clickedTag)
        })

      </script>


      {{-- 地図検索 --}}


      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.3/dist/leaflet.css"
            integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ=="
            crossorigin=""/>
      <!-- Make sure you put this AFTER Leaflet's CSS -->
      <script src="https://unpkg.com/leaflet@1.3.3/dist/leaflet.js"
              integrity="sha512-tAGcCfR4Sc5ZP5ZoVz0quoZDYX5aCtEm/eu1KhSLj2c9eFrylXZknQYmxUssFaVJKvvc0dJQixhGjG2yXWiV9Q=="
              crossorigin=""></script>

      <link rel="stylesheet" href="//unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css" type="text/css">
      <script src="//unpkg.com/leaflet-gesture-handling"></script>


      {{-- TODO 地図系も本来はモーダルのcomponent側に移動させる。--}}
      <script>

        var map = L.map('map', {
          center: [{{ config('const.LATLNG.LAT') }}, {{ config('const.LATLNG.LNG') }}],
          zoom: {{ env('MAP_ZOOM', 10) }},
          gestureHandling: true
        });

        // デフォルト値は真備
        var systemCenterLat = {{ config('const.LATLNG.LAT') }}
        var systemCenterLng = {{ config('const.LATLNG.LNG') }}

        var latlng = L.latLng(
          systemCenterLat,
          systemCenterLng,
        );

        map.setView(latlng, 15);


        //タイルサーバー(選択可能のコントロールも表示)を登録します。
        L.control.layers({
          "OpenStreetMap":L.tileLayer('https://c.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors, '
          }).addTo(map),
          "地理院 標準地図":L.tileLayer('https://cyberjapandata.gsi.go.jp/xyz/std/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: 'Map data <a href="https://maps.gsi.go.jp/development/ichiran.html" target="_blank">国土地理院</a>'
          }),
          "地理院 淡色地図":L.tileLayer('https://cyberjapandata.gsi.go.jp/xyz/pale/{z}/{x}/{y}.png', {
            minZoom: 2,
            maxZoom: 18,
            attribution: 'Map data <a href="https://maps.gsi.go.jp/development/ichiran.html" target="_blank">国土地理院</a>'
          })
        }).addTo(map);



        var shelters = @json($all_shelters_for_modal);

        console.log(shelters)

        shelters.forEach(function(shelter, index) {

          console.log(shelter)
          console.log(index)

          if (shelter.lat == '') {
            return;
          }

          var marker = L.marker(L.latLng(
            shelter.lat,
            shelter.lng,
          ));

          marker.addTo(map).bindPopup("<div style='width: 600px;'>" +
            "<strong>"+shelter.name+"</strong> <br> <br>" +
            '<button type="button" class="modal-shelter-select-btn btn btn-default" data-dismiss="modal" data-shelter-id="'+shelter.id+'">選択</button>' +
            '</div>')
        })


        // click はイベントバブリング可能なので上位ノードで監視して、leaflet上で生成されたpopup上のボタンも検知できるよういしている。
        // 参考 https://teratail.com/questions/33142   https://uhyohyo.net/javascript/3_3.html
        $(document.body).on('click', '.modal-shelter-select-btn', function() {

          var selectedShelterId = $(this).data('shelter-id');

          $('#all_shelter_select').val(selectedShelterId);
        });




        // 地図の移動を検知して
        // map.on('move', function(e) {
        //
        //   var center = map.getCenter()
        //
        //   // マーカーを移動
        //   marker.setLatLng(center)
        //
        //   // 緯度軽度を取得
        //   // $('#lat').val(center.lat)
        //   // $('#lng').val(center.lng)
        //
        // });



        // 現在地ボタン
        var locateOption = {
          position: 'bottomright',
          drawCircle: false,
          icon: "fas fa-map-marker-alt",
          strings: {
            title: "現在地を表示",
            popup: "いまここ"
          },
          locateOptions: {
            maxZoom: 18
          }
        }

        // TODO 呼ぶと落ちている
        // L.control.locate(locateOption).addTo(map);


        // モーダル、タブで地図を表示する場合canvasの表示サイズが動的に変わるようなので表示時に地図サイズの調整が必要
        // $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        //   // e.target // newly activated tab
        //   // e.relatedTarget // previous active tab
        //
        //   setTimeout(function() {
        //     map.invalidateSize();
        //   }, 1);
        //
        // })

        // モーダル、タブで地図を表示する場合canvasの表示サイズが動的に変わるようなので表示時に地図サイズの調整が必要
        // $('#shelterMapModal').on('show.bs.modal', function (event) {
        //
        //   console.log("show")
        //
        //   setTimeout(function() {
        //     map.invalidateSize();
        //   }, 1);
        //
        // })
        $('#shelterMapModal').on('shown.bs.modal', function (event) {

          // showだと間に合わない。shownである必要がある。

          setTimeout(function() {
            map.invalidateSize();
          }, 10);

        })



      </script>


  @component("components.shelter_search_modal_script")@endcomponent

@endsection
