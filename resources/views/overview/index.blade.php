@extends('layouts.app')

@section('content')
<?php
$default_value = null; // デフォルト値としてnullを指定
?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">支援概況</h1>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- MAIN CONTENT -->
        <section class="content">
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

                <section class="col-lg-12">
                    {!! Form::model($Reports, ['route' => ['overview'], 'class' => 'form-horizontal', 'method' => 'GET']) !!}
                    @csrf
                    <!--都道府県選択 -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-1">
                                <!-- 災害情報選択 -->
                                <label>災害情報</label>
                            </div>
                            <div class="col-sm-2">
                                {{ Form::select('disaster_id', $disasters, $disaster_id, ['class' => 'form-control select2', 'placeholder' => "選択してください"]) }}
                                {{ Form::inputError('disaster_id') }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-1">
                                <!-- タグ -->
                                <label>タグ</label>
                            </div>
                            <div class="col-sm-10">
                                {{ Form::text('tag_list', null, ['class' => 'form-control']) }}
                                {{ Form::inputError('tag_list') }}
                                <p>
                                    @foreach($tags as $tag)
                                        <span class="badge badge-light add-tag-button" data-tag="{{ $tag }}">{{ $tag }}</span>
                                    @endforeach
                                </p>
                            </div>
                        </div>
                        <div class="row">
                        </div>
                        <div class="row">
                            <div class="col-sm-1">
                            </div>  
                            <!-- 市町村選択 -->
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>市区町村</label>
                                    {{ Form::select('local_government_id', $local_government_pulldowns, old('local_government_id', $local_government_id), ['class' => 'select2 form-control', 'placeholder' => '選択してください']) }}
                                    {{ Form::inputError('local_government_id') }}
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>支援種別</label>
                                    <div class="form-group">
                                        {{ Form::select('support_category1_id', $support_category1_pulldown_list, old('support_category1_id', $support_category1_id), ['class' => 'form-control select2', 'placeholder' => "選択してください"]) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>検索対象期間</label>
                                    <div class="input-group">
                                        {{ Form::text('start_date', old(date('Y-m-d', strtotime('-1 month')), $start_date), ['class' => 'form-control datepicker']) }}
                                        <div class="input-group-append">
                                            <span class="input-group-text">から</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>　</label>
                                    <div class="input-group">
                                        {{ Form::text('end_date', old(date('Y-m-d', strtotime('-1 month')), $end_date), ['class' => 'form-control datepicker']) }}
                                        <div class="input-group-append">
                                        <span class="input-group-text">まで</span>
                                        </div>
                                    </div>    
                                 </div>
                              </div>
                        </div>                  
                    </div>
                <div class="card-footer">
                    <button name="submit" class="btn btn-primary" value="submit"><i class="fas fa-search"></i> 検索</button>
                </div>
            </section> <!-- section col-lg-12-->
            <section class="col-lg-12 connectedSortable">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>タグ</th>
                            <th>支援種別</th>
                            <th>活動団体数(延団体数)</th>
                            <th>
                                <i class="nav-icon fas fa-flag text-danger"></i>赤
                            </th>
                            <th>
                                <i class="nav-icon fas fa-flag text-warning"></i>黄
                            </th>
                            <th>
                                <i class="nav-icon fas fa-flag text-info"></i>青
                            </th>
                            <!-- 他に必要なカラム -->
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($Reports as $Report)
                            <tr>
                                <td>{{ $loop->iteration }}</td> <!-- ここで連番を表示 -->
                                <td>{{$Report->tag_name}}</td>
                                <td>{{$Report->support_category1_name}} : {{$Report->support_category2_name}}</td>
                                <td>{{$Report->report_count}} ({{$Report->organization_count}})</td>
                                <td>{{$Report->signal_danger_count}} ({{$Report->signal_danger_shelter_count}})</td>
                                <td>{{$Report->signal_warning_count}} ({{$Report->signal_warning_shelter_count}})</td>
                                <td>{{$Report->signal_info_count}} ({{$Report->signal_info_shelter_count}})</td>
                                <!-- 他に必要なカラムの値 -->
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </div><!-- ontainer-fluid -->

        </section><!-- content -->
    </div>


{{-- / 1入力項目 --}}
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

  // Chainable event listeners
  tagify.on('add', onAddTag)
  .on('remove', onRemoveTag)
  .on('inval    ', onInvalidTag);

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
@endsection