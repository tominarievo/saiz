@extends('layouts.app')

@section('content')

  @can("admin_help", \App\Page::class)

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="container-fluid">

      <div class="panel">
        <div class="panel-body">

          <h4>管理者マニュアル</h4>

          <a href="{{ asset('assets/administrator_manual.pdf') }}" download="利用者マニュアル（管理者編）">利用者マニュアル（管理者編）をダウンロード</a>

        </div>
      </div>

    </div>
    <!-- END CONTAINER FLUID -->
  </div>
  <!-- END MAIN CONTENT -->
  @else

    <div class="main-content">
      <div class="container-fluid">

        <div class="alert alert-danger" style="margin-top: 200px;">
          閲覧権限がありません。
        </div>

      </div>
      <!-- END CONTAINER FLUID -->
    </div>


  @endcan

@endsection
