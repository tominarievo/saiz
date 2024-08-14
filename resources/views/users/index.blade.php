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

      <div class="clearfix margin-bottom-10">
        <a href="{{ route('users.create')."?organization_id={$organization_id}" }}" class="btn btn-primary pull-right">データ管理者を登録</a>
      </div>

      @if(filled($users))
        <!-- TABLE -->
        <div class="panel">
          <table class="table table-bordered tbl-stripe tbl-hover">
            <thead>
            <tr class="tr-clr1">
              <th>データ管理者名</th>
              <th>権限</th>
              <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
              <tr>
                <td>
                  {{ $user->name }}
                </td>
                <td>
                  {{ $user->role->name }}
                </td>
                <td>
                  @can('update', $user)
                    <a href="{{ route('users.edit', ['user' => $user->id]) }}" class="btn btn-default">編集</a>
                  @endcan

                  @can('delete', $user)
                    {!! Form::open(['route' => ['users.destroy', $user->id], 'style' => 'display:inline']) !!}
                    @method('DELETE')
                    <button class="btn btn-danger" onclick="if ( ! confirm('削除します。よろしいですか？')){return false;}">削除</button>
                    {!! Form::close() !!}
                  @endcan
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
        <!-- END TABLE -->
      @else
        <div class="alert alert-warning">
          <div class="pT-1 pB-1">
            <strong>該当する項目が見つかりませんでした。</strong>

          </div>
        </div>
      @endif

      <br>
      {{ $users->links('vendor.pagination.bootstrap-4') }}

    </div>
    <!-- END CONTAINER FLUID -->
  </div>
  <!-- END MAIN CONTENT -->

@endsection
