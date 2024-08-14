@extends('layouts.app')

@section('content')


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">{{ filled(\Illuminate\Support\Facades\Auth::user()->organization_id) ? "".\Illuminate\Support\Facades\Auth::user()->organization->name."" : '事務局' }}のHOME

              　<a class="btn btn-primary" href="{{ route('reports.create') }}">
              <i class="fas fa-pen"></i> 日々の活動内容を記録する
            </a>

            </h1>
          </div><!-- /.col -->

        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <!-- Small boxes (Stat box) -->


        {{-- お知らせ --}}
        <section class="content">
          <div class="container-fluid">
            <!-- Small boxes (Stat box) -->

            <div class="row">
              <section class="col-lg-6 connectedSortable">

                <div class="card card-info">
                  <div class="card-header">
                    <h3 class="card-title">お知らせ</h3>
                  </div>
                  <!-- /.card-header -->
                  <div class="card-body">

                    <div class="">

                      @foreach($information_list as $information)

                        <div class="card card-info">
                          <div class="card-body">

                            <strong>
                              {{ $information->published_at->format("Y/m/d") }}
                            </strong>
                            <a href="{{ route('information.show', ['information' => $information->id]) }}">
                              {{ \Illuminate\Support\Str::limit($information->title, 40) }}
                            </a>
                          </div>
                        </div>

                      @endforeach

                    </div>

                  </div>

                </div>

              </section>

              <section class="col-lg-6 connectedSortable">

                @component("components.home_timeline") @endcomponent

              </section>

            </div>
          </div>
        </section>


      @if (Auth::user()->isAdmin())

          <div class="row">

          <section class="col-lg-8 connectedSortable">

          <div class="card card-info">
            <div class="card-header">
              <h3 class="card-title">支援先状況</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">



              {!! Form::model($condition, ['route' => ['home'], 'class' => 'form-horizontal', 'method' => 'GET']) !!}


              <div class="row">
                <div class="col-sm-6">
                </div>

                <div class="col-sm-3">

                  <div class="form-group">
                    <label>支援の状態を集計する日数</label>

                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text">
                        開始日
                        </span>
                      </div>
                      {{ Form::text('start_date', null, ['class' => 'form-control datepicker']) }}
                    </div>

                  </div>
                </div>
                <div class="col-sm-3">
                  <div class="form-group">

                    <label>　</label>

                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text">
                          終了日
                        </span>
                      </div>
                      {{ Form::text('end_date', null, ['class' => 'form-control datepicker']) }}
                    </div>

                  </div>
                </div>
              </div>



              <div class="row">

                <div class="col-sm-6">
                </div>

                <div class="col-sm-3">
                  <div class="form-group">

                    <label>災害情報</label>

                    <div class="form-group">

                      {{ Form::select('disaster_id', $disasters, null, ['class' => 'form-control select2', 'placeholder' => '選択してください']) }}

                    </div>

                  </div>
                </div>

                <div class="col-sm-3">
                  <div class="form-group">

                    <label>　</label>

                    <div class="form-group">

                      <button type="submit" class="btn btn-primary">適用</button>

                    </div>

                  </div>
                </div>

              </div>

              {!! Form::close() !!}

              <div class="scroll-table">
              <table class="table table-bordered">
                <thead>
                <tr>
                  <th>種別</th>
                  <th>最終更新</th>
                  <th style="width: 200px"><i class="nav-icon fas fa-flag text-danger"></i> 非常に課題あり</th>
                  <th style="width: 200px"><i class="nav-icon fas fa-flag text-warning"></i> 一部課題あり</th>
                  <th style="width: 200px"><i class="nav-icon fas fa-flag text-info"></i> OK</th>
                </tr>
                </thead>
                <tbody>

                {{--

                category1をループで回し、日付とステータスが一緒に入っている配列から数値を取得している。

                --}}
                @foreach($count_list as $category1_element)

                  <tr>
                    <td>{{ $category1_element->support_category1->name }}</td>

                    <td>
                      @if ($category1_element->last_report)

                        <a href="{{ route('shelters.index', ["support_category1_id" => $category1_element->support_category1->id, "signal_id" => $category1_element->last_report_signal, "start_date" => $category1_element->last_report->report_date, "end_date" => $category1_element->last_report->report_date]) }}">
                          <i class="nav-icon fas fa-flag text-{{ \App\Signal::getSignal($category1_element->last_report_signal)->css_class }}"></i>
                          {{ $category1_element->last_report->report_date }}
                        </a>
                      @endif
                    </td>

                    @foreach(\App\Signal::getList() as $signal)
                    <td>
                      @foreach($day_list as $day_carbon)
                        <a href="{{ route('shelters.index', ["support_category1_id" => $category1_element->support_category1->id, "signal_id" => $signal->id, "start_date" => $day_carbon->format('Y/m/d'), "end_date" => $day_carbon->format('Y/m/d')]) }}">

                            <span class="badge {{ $loop->first ? "bg-".$signal->css_class : 'badge-dark' }}" data-toggle="tooltip" title="{{ $day_carbon->format('m/d') }}">
                              @if (\Illuminate\Support\Arr::has($category1_element->list, "{$day_carbon->format('Y/m/d')}.{$signal->id}"))
                                {{ \Illuminate\Support\Arr::get($category1_element->list, "{$day_carbon->format('Y/m/d')}.{$signal->id}") }}
                              @else
                                -
                              @endif
                          </span>
                        </a>
                      @endforeach
                    </td>
                    @endforeach

                  </tr>

                @endforeach

                </tbody>
              </table>

              </div>

              <br>
              <br>

              <div>
                <h5>凡例</h5>
                @foreach($day_list as $day_carbon)

                  @if ($loop->first)
                    <span class="badge badge-light">
                  @else
                    <span class="badge badge-light">
                  @endif

                  {{ $day_carbon->format('m/d') }}</span>
                @endforeach

              </div>

            </div>
            <!-- /.card-body -->

          </div>
          </section>


          <section class="col-lg-4 connectedSortable">

            @component("components.home_timeline") @endcomponent

          </section>
        </div>

        @else



        @endif

        @if (Auth::user()->isAdmin())
        <!-- Main row -->
        <div class="row">
          <!-- Left col -->
          <section class="col-lg-12 connectedSortable">

            <!-- DIRECT CHAT -->
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">事務局メニュー</h3>
              </div>

              <!-- /.card-header -->
              <div class="card-body">

                <a class="btn btn-primary" href="{{ route('organizations.index') }}">
                  {{--                  <span class="badge bg-danger">531</span>--}}
                  <i class="fas fa-building"></i> 支援団体
                </a>

                <a class="btn btn-primary" href="{{ route('shelters.index') }}">
                  {{--                  <span class="badge bg-danger">531</span>--}}
                  <i class="fas fa-landmark"></i> 支援先
                </a>

                <a class="btn btn-primary" href="{{ route('disasters.index') }}">
                  {{--                  <span class="badge bg-danger">531</span>--}}
                  <i class="fas fa-info"></i> 災害情報
                </a>


                <a class="btn btn-primary">
                  {{--                  <span class="badge bg-danger">531</span>--}}
                  <i class="fas fa-building"></i> 支援団体 - 支援先マッチング
                </a>



              </div>

            </div>
            <!--/.direct-chat -->

          </section>
          <!-- /.Left col -->
          <!-- right col (We are only adding the ID to make the widgets sortable)-->

          <!-- right col -->
        </div>
        <!-- /.row (main row) -->
        @endif



      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->



    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <!-- Small boxes (Stat box) -->

        <div class="row">

          <section class="col-lg-12 connectedSortable">

            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">支援状況マップ</h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">



                {!! Form::model($condition, ['route' => ['home'], 'class' => 'form-horizontal', 'method' => 'GET']) !!}


                <div class="row">
                  <div class="col-sm-9">

                    <div id="map" style="width:100%;height:600px"></div>

                  </div>

                  <div class="col-sm-3" style="height: 600px; overflow-y: scroll;">

                    <div class="form-group">
                      <label>支援先</label>

                      <br>
                      <label>
                      <input type="checkbox"
                             id="show_closed_shelter"
                             name="show_closed_shelter"
                             value="true"
                             {{ $condition->show_closed_shelter === 'true' ? "checked" : "" }} /> 支援終了した支援先も表示する
                      </label>

                      <table class="table ">
                        @foreach($shelters as $shelter)
                          @if($shelter->lat != 0 && $shelter->lng != 0)
                        <tr>
                          <td>
                            <button type="button" class="map-shelter btn btn-sm btn-outline-dark"
                                    data-lat="{{ $shelter->lat }}" data-lng="{{ $shelter->lng }}"
                            >
                              @if ($shelter->signal_id == \App\Signal::NO_SIGNAL)
                                <i class="nav-icon fas fa-comment text-{{ $shelter->signal_css }}"></i>
                              @else
                                <i class="nav-icon fas fa-flag text-{{ $shelter->signal_css }}"></i>
                              @endif

                              {{ $shelter->name }}
                            </button>
                          </td>
                        </tr>
                          @endif
                        @endforeach
                      </table>

                    </div>
                  </div>
                </div>

                <br>
                <br>

              </div>
              <!-- /.card-body -->

            </div>
          </section>
        </div>

      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->

  </div>



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

    // デフォルト
    var systemCenterLat = {{ config('const.LATLNG.LAT') }}
      var systemCenterLng = {{ config('const.LATLNG.LNG') }}

      var latlng = L.latLng(
      systemCenterLat,
      systemCenterLng,
    );

    map.setView(latlng, {{ config('const.MAP_PARAM.ZOOM') }} );


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


    var shelters = @json($shelters);

    shelters.forEach(function(shelter, index) {

      if (shelter.lat == '') {
        return;
      }

      var redIcon = L.icon({
        iconUrl: '{{ asset('img/flag_danger.png') }}',
        iconSize:     [36, 36], // size of the icon
      });

      var yellowIcon = L.icon({
        iconUrl: '{{ asset('img/flag_warning.png') }}',
        iconSize:     [36, 36], // size of the icon
      });

      var blueIcon = L.icon({
        iconUrl: '{{ asset('img/flag_info.png') }}',
        iconSize:     [36, 36], // size of the icon
      });

      var grayIcon = L.icon({
        iconUrl: '{{ asset('img/flag_gray.png') }}',
        iconSize:     [36, 36], // size of the icon
      });

      var icons = {
        1: blueIcon,
        2: yellowIcon,
        3: redIcon,
        9: grayIcon
      };

      // アイコンの表示順序。シグナルが強い用が前へ
      const orderOffset = {
        1: 100,
        2: 200,
        3: 300,
      };

			// マウスホバー時に z-index に一時加算される値
      const riseOffset = 500;

      const zIndex = (shelter.signal_id in orderOffset) ? orderOffset[shelter.signal_id] : 0;

      var marker = L.marker(L.latLng(
        shelter.lat,
        shelter.lng,
      ),{
        icon: icons[(shelter.signal_id)],
        riseOnHover: true,
        riseOffset: riseOffset,
        zIndexOffset: zIndex
      });

      var content = "<div style='width: 600px;'>" +
        "<a href='#' class=''><strong>"+shelter.name+"</strong></a> <br> <br>";

      shelter.category1_list.forEach(function(category1) {
        content += "<a href='/reports?shelter_id="+shelter.id+"&support_category1_id="+category1.id+"&include_disabled_shelters="+ $("#show_closed_shelter").prop('checked') + "'><span><i class=\"nav-icon fas fa-flag text-"+ category1.signal_css +"\"></i> "+ category1.label +"　</span></a> <hr>";
      })

      content +='<a class="modal-shelter-select-btn btn btn-lg btn-default" data-dismiss="modal" href="/shelters/'+shelter.id+'">支援先の詳細</a>' +
        '</div>';

      marker.addTo(map).bindPopup(content)
    })


    {{-- map横の一覧のクリック時の地図の中心移動 --}}
    $(".map-shelter").on('click', function() {

      var target = $(this);

      var latlng = L.latLng(
        target.data('lat'),
        target.data('lng'),
      );

      map.setView(latlng, map.getZoom());
    })

    // 終了した支援先を表示するかどうかのチェックボックスの制御
    $("#show_closed_shelter").on("change", function() {
      var checkbox = $(this);
      location.href = "{{ route('home') }}?show_closed_shelter="+checkbox.prop('checked');
    })

  </script>


  <script>

    $('#timeline_disaster_id').on("change", function() {
      location.href = '{{ route('home') }}?timeline_disaster_id='+$(this).val()
    });

  </script>


@endsection
