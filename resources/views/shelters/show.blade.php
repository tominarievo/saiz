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

            <!-- /.card-header -->
            <div class="card-body">


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  名称
                </label>

                <div class="col-sm-10">

                  {{ $shelter->name }} ({{ $shelter->npo_col_2 }})

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  開設状況
                </label>

                <div class="col-sm-10">

                  @if ($shelter->status)
                    開設中
                  @else
                    閉鎖済み
                  @endif

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  指定避難所
                </label>

                <div class="col-sm-10">

                  @if ($shelter->is_designated)
                    <i class="fas fa-check"></i>
                  @else
                    --
                  @endif

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

                  {{ $shelter->localGovernment->prefecture->name }}{{ $shelter->localGovernment->name }}{{ $shelter->npo_col_3 }}{{ $shelter->npo_col_4 }}

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

                  {{ $shelter->representative }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  電話番号
                </label>

                <div class="col-sm-10">

                  {{ $shelter->npo_col_8 }}

                </div>
              </div>
              {{-- / 1入力項目 --}}



              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  内線番号
                </label>

                <div class="col-sm-10">

                  {{ $shelter->npo_col_9 }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              <hr>


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  標高
                </label>

                <div class="col-sm-10">

                  {{ $shelter->npo_col_7 }}

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

                  {{ $shelter->npo_col_14 }}

                </div>
              </div>
              {{-- / 1入力項目 --}}




              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  想定収容人数
                </label>

                <div class="col-sm-10">

                  {{ $shelter->npo_col_15 }}

                </div>
              </div>
              {{-- / 1入力項目 --}}




              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  対象となる町内会・自治会
                </label>

                <div class="col-sm-10">

                  {{ $shelter->npo_col_16 }}

                </div>
              </div>
              {{-- / 1入力項目 --}}




              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  URL
                </label>

                <div class="col-sm-10">

                  {!! \App\UtilLogic::getEditedContent($shelter->npo_col_17) !!}

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

                  {!! \App\UtilLogic::getEditedContent($shelter->npo_col_18) !!}

                </div>
              </div>
              {{-- / 1入力項目 --}}




              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  緯度、経度
                </label>

                <div class="col-sm-10">

                  <div id="map" style="width:100%;height:500px"></div>

                  <div class="row">
                    <div class="col-sm-6">
                      <label>緯度</label>
                      {{ $shelter->lat }}

                      {{-- 地図処理の共通化用 --}}
                      {{ Form::hidden('lat', null, ["id" => "lat"]) }}

                    </div>
                    <div class="col-sm-6">
                      <label>経度</label>
                      {{ $shelter->lng }}

                      {{-- 地図処理の共通化用 --}}
                      {{ Form::hidden('lng', null, ["id" => "lng"]) }}

                    </div>
                  </div>

                </div>
              </div>
              {{-- / 1入力項目 --}}



            </div>

            <div class="card-footer">
              {{--                    @can('update', $user)--}}
              <a href="{{ route('shelters.edit', ['shelter' => $shelter->id]) }}" class="btn btn-primary"><i class="fas fa-edit"></i> 編集</a>
              {{--                    @endcan--}}

              {{--                    @can('delete', $user)--}}
              {!! Form::open(['route' => ['shelters.destroy', $shelter->id], 'style' => 'display:inline']) !!}
              @method('DELETE')
              <button class="btn btn-danger" onclick="if ( ! confirm('削除します。よろしいですか？')){return false;}"><i class="fas fa-trash"></i> 削除</button>
              {!! Form::close() !!}
              {{--                    @endcan--}}
            </div>

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
      center: [{{ $shelter->lat }}, {{ $shelter->lng }}],
      zoom: {{ env('MAP_ZOOM', 15) }},
      gestureHandling: true
    });

    // デフォルト値は真備
    var latlng = L.latLng({{ $shelter->lat }}, {{ $shelter->lng }});

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
