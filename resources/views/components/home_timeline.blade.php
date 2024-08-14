<div class="card card-info">
  <div class="card-header">
    <h3 class="card-title">最近の活動状況</h3>
  </div>
  <!-- /.card-header -->
  <div class="card-body" style="height: 600px; overflow: scroll">

    <div class="row">
      <div class="col">
        <div class="form-group">

          {!! Form::model($condition, ['route' => ['home'], 'class' => 'form-horizontal', 'method' => 'GET']) !!}

          {{ Form::select('timeline_disaster_id', $disasters, null, ['id' => 'timeline_disaster_id', 'class' => 'form-control select2', 'placeholder' => '--災害情報を選択してください--']) }}

          {!! Form::close() !!}
        </div>
      </div>
    </div>

    <div class="">

      @foreach($timeline_reports as $timeline_report)

        <div class="card card-info">
          <div class="card-body">

            <strong>
              <a href="{{ route('reports.show', ['report' => $timeline_report->id]) }}">
                {{ $timeline_report->report_date }}
              </a>
            </strong>

            @foreach($timeline_report->getSupportCategoryInfo() as $support_category_info)

              <span>
                                @if ($support_category_info["signal"] == 1)
                  <i class="nav-icon fas fa-flag text-info"></i>
                @elseif ($support_category_info["signal"] == 2)
                  <i class="nav-icon fas fa-flag text-warning"></i>
                @elseif ($support_category_info["signal"] == 3)
                  <i class="nav-icon fas fa-flag text-danger"></i>
                @elseif ($support_category_info["signal"] == \App\Signal::NO_SIGNAL)
                  <i class="nav-icon fas fa-comment text-gray"></i>
                @endif
                {{ $support_category_info["name"] }}
                              </span>

            @endforeach
　
            [
            {{ $timeline_report->organization->name }}
            <i class="nav-icon fas fa-arrow-right"></i>
            <a href="{{ route('shelter_views.index')."?shelter_id=".$timeline_report->shelter_id }}">
              {{ $timeline_report->shelter->name }}
            </a>
            /{{ $timeline_report->disaster->name }}
            ]
            <br>

            {{ \Illuminate\Support\Str::limit($timeline_report->comment, 75) }}

          </div>
        </div>

      @endforeach

    </div>

  </div>
  <!-- /.card-body -->
  <div class="card-footer">
    <a href="{{ route('shelter_views.index') }}">もっと見る</a>
  </div>

</div>
