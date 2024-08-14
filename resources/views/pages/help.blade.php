@extends('layouts.app')

@section('content')

  @can("help", \App\Page::class)

    <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="container-fluid">


      <h4>管理者マニュアル</h4>
      <div class="panel">
        <div class="panel-body">
          <p style="padding-top: 10px;">
            <a href="{{ asset('assets/administrator_manual.pdf') }}" download="利用者マニュアル（管理者編）">利用者マニュアル（管理者編）をダウンロード</a>
          </p>
        </div>
      </div>

      <h4>登録について</h4>
      <div class="panel">
        <div class="panel-body">
          <p><img src="{{ asset('assets/img/flow1.png') }}"></p>
        </div>
      </div>

      <h4>データセットの登録方法は4通りあります</h4>
      <div class="panel">
        <div style="padding:30px;">
          <div class="row">
            <div class="col-xs-3">
              <h4 class="text-primary">新規に入力して登録</h4>
              <p><img src="{{ asset('assets/img/flow2-1.png') }}"></p>
              <p>白紙の状態から作成します。各項目をご自身で入力します。</p>
            </div>
            <div class="col-xs-3">
              <h4 class="text-primary">Myクリップから登録</h4>
              <p><img src="{{ asset('assets/img/flow2-2.png') }}"></p>
              <p>流用できそうなデータセットをあらかじめMyクリップに登録しておきます。登録済みのデータセットを引用して新規登録をします。</p>
            </div>
            <div class="col-xs-3">
              <h4 class="text-primary">テンプレートから登録</h4>
              <p><img src="{{ asset('assets/img/flow2-3.png') }}"></p>
              <p>用意されているテンプレートを引用して新規登録することができます。</p>
            </div>
            <div class="col-xs-3">
              <h4 class="text-primary">既存データを引用して登録</h4>
              <p><img src="{{ asset('assets/img/flow2-4.png') }}"></p>
              <p>既に登録したデータセットを引用して登録することができます。</p>
            </div>
          </div>
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
