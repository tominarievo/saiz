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

    @if (session()->has('status'))
    <div class="alert alert-success">
      {{ session('status') }}
    </div>
    @endif

    <!-- ROW -->
      <div class="row">

        <section class="col-lg-12 connectedSortable">

          <div class="card card-info">

          {!! Form::model($shelter, ['route' => ['shelters.update', $shelter->id], 'class' => 'form-horizontal', ]) !!}
          @method('PUT')

            <!-- /.card-header -->
            <div class="card-body">

              <p>必要事項を入力し、画面下のボタンを押してください。</p>


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  ステータス
                </label>

                <div class="col-sm-10">
                  <label for="status" class="">
                    {{ Form::checkbox('status', true, null, ['class' => '', 'id' => 'status']) }} 有効
                  </label>

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  指定避難所
                </label>

                <div class="col-sm-10">
                  <label for="is_designated" class="">
                    {{ Form::checkbox('is_designated', '1', null, ['class' => '', 'id' => 'is_designated']) }} 指定避難所
                  </label>

                  {{ Form::inputError('is_designated') }}
                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  名称
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::text('name', null, ['class' => 'form-control', 'id' => 'matchingCondition']) }}
                  {{ Form::inputError('name') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  名称かな
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_2', null, ['class' => 'form-control', 'id' => 'matchingCondition']) }}
                  {{ Form::inputError('npo_col_2') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              <hr>

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  市区町村
                </label>

                <div class="col-sm-10">

                  {{ Form::select('local_government_id', $local_government_pulldowns, null, ['class' => 'select2 form-control', 'placeholder' => '選択してください']) }}
                  {{ Form::inputError('local_government_id') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  住所(町丁目以降)
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_3', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_3') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  ビル名等
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_4', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_4') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              <hr>


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  代表者
                </label>

                <div class="col-sm-10">

                  {{ Form::text('representative', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('representative') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  電話番号
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_8', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_8') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}



              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  内線番号
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_9', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_9') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              <hr>



              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  緯度、経度
                </label>

                <div class="col-sm-10">


                      <p><small>※背景地図をマウスで動かしてピンの位置をスポットにあわせてください。あわせた位置の緯度経度を自動的に取得します。</small></p>

                      <div id="map" style="width:100%;height:500px"></div>

                      <div class="row">
                        <div class="col-sm-6">
                          <label>緯度</label>
                          {{ Form::text('lat', null, ['class' => 'form-control', 'id' => 'lat']) }}

                        </div>
                        <div class="col-sm-6">
                          <label>経度</label>
                          {{ Form::text('lng', null, ['class' => 'form-control', 'id' => 'lng']) }}

                        </div>
                      </div>


                  {{ Form::inputError('lat') }}
                  {{ Form::inputError('lng') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  標高
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_7', null, ['class' => 'form-control', 'id' => '']) }}
                  (単位:m)
                  {{ Form::inputError('npo_col_7') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}



              <hr>


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  指定支援先との重複
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_14', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_14') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}




              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  想定収容人数
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_15', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_15') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}




              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  対象となる町内会・自治会
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_16', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_16') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}




              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  URL
                </label>

                <div class="col-sm-10">

                  {{ Form::text('npo_col_17', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_17') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              <hr>

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  備考
                </label>

                <div class="col-sm-10">

                  {{ Form::textarea('npo_col_18', null, ['class' => 'form-control', 'id' => '']) }}
                  {{ Form::inputError('npo_col_18') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}




            </div>

            <div class="card-footer">

              <button name="submit" class="btn btn-primary" value="submit">登録</button>
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



  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.3/dist/leaflet.css"
        integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ=="
        crossorigin=""/>
  <!-- Make sure you put this AFTER Leaflet's CSS -->
  <script src="https://unpkg.com/leaflet@1.3.3/dist/leaflet.js"
          integrity="sha512-tAGcCfR4Sc5ZP5ZoVz0quoZDYX5aCtEm/eu1KhSLj2c9eFrylXZknQYmxUssFaVJKvvc0dJQixhGjG2yXWiV9Q=="
          crossorigin=""></script>

  <link rel="stylesheet" href="//unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css" type="text/css">
  <script src="//unpkg.com/leaflet-gesture-handling"></script>



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
      $('#lat').val() != '' ? $('#lat').val() : systemCenterLat,
      $('#lng').val() != '' ? $('#lng').val() : systemCenterLng,
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


    var marker = L.marker(latlng);

    marker.addTo(map)

    // 地図の移動を検知して
    map.on('move', function(e) {

      var center = map.getCenter()

      // マーカーを移動
      marker.setLatLng(center)

      // 緯度軽度を取得
      $('#lat').val(center.lat)
      $('#lng').val(center.lng)

    });



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

    L.control.locate(locateOption).addTo(map);


  </script>

@endsection
