@extends('layouts.app')

@section('content')

  <!-- MAIN CONTENT -->
  <div class="main-content">
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

      @if(filled($pages))
        <!-- TABLE -->
        <div class="panel">
          <table class="table table-bordered tbl-stripe tbl-hover">
            <thead>
            <tr class="tr-clr1">
              <th>ページ名</th>
              <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($pages as $page)
              <tr>
                <td>
                  {{ $page->title }}
                </td>
                <td>
                  @can('update', $page)
                    <a href="{{ route('pages.edit', ['page' => $page->id]) }}" class="btn btn-default">編集</a>
                  @endcan
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
        <!-- END TABLE -->

      @endif

    </div>
    <!-- END CONTAINER FLUID -->
  </div>
  <!-- END MAIN CONTENT -->

@endsection
