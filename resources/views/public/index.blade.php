@extends('layouts.public')

@section('title', '災害情報 | 災図')

@section('content')

  <div class="public-hero">
    <div class="container">
      <h1>災害情報</h1>
      <p>
        ログインなしで、最近の災害@if (config('public.show_support_status', false))、本日の支援先状況@endif、最新のお知らせを確認できます。
      </p>
    </div>
  </div>

  <section class="content py-4">
    <div class="container">
      <div class="row">

        <div class="col-lg-7">

          <div class="card card-outline card-danger">
            <div class="card-header">
              <h2 class="card-title public-card-title">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                最近の災害
              </h2>
            </div>
            <div class="card-body">
              @forelse ($disasters as $disaster)
                <div class="border-bottom pb-3 mb-3">
                  <div class="d-flex flex-wrap align-items-center">
                    <h3 class="h5 mb-2 mr-2">
                      <a href="{{ route('disasters.show', $disaster->id) }}">{{ $disaster->name }}</a>
                    </h3>
                    @if ($disaster->is_catastrophic_disaster)
                      <span class="badge badge-danger mb-2">激甚災害</span>
                    @endif
                  </div>

                  @if ($disaster->localGovernments->isNotEmpty())
                    <div class="mb-1">
                      @foreach ($disaster->localGovernments as $localGovernment)
                        <span class="badge badge-light border">
                          {{ $localGovernment->prefecture->name }} {{ $localGovernment->name }}
                        </span>
                      @endforeach
                    </div>
                  @endif

                  <div class="text-muted small">
                    @if (filled($disaster->event_date))
                      発生年月日: {{ $disaster->event_date }}
                    @endif
                    @if (filled($disaster->npo_col_2))
                      <span class="ml-2">年度: {{ $disaster->npo_col_2 }}</span>
                    @endif
                  </div>
                </div>
              @empty
                <p class="text-muted mb-0">現在、公開中の災害情報はありません。</p>
              @endforelse
            </div>
          </div>

          @if (config('public.show_support_status', false))
            <div class="card card-outline card-info">
              <div class="card-header">
                <h2 class="card-title public-card-title">
                  <i class="fas fa-flag mr-2"></i>
                  本日の支援先状況
                </h2>
              </div>
              <div class="card-body">
                <div class="row text-center">
                  @foreach ($signalCounts as $signal)
                    <div class="col-4">
                      <div class="public-count text-{{ $signal['css_class'] }}">{{ $signal['count'] }}</div>
                      <span class="badge badge-{{ $signal['css_class'] }}">{{ $signal['label'] }}</span>
                    </div>
                  @endforeach
                </div>
                <p class="text-muted small mt-3 mb-0">
                  本日の日報に基づく支援先の状況です。詳細はログイン後に確認できます。
                </p>
              </div>
            </div>
          @endif

        </div>

        <div class="col-lg-5">

          <div class="card card-outline card-primary">
            <div class="card-header">
              <h2 class="card-title public-card-title">
                <i class="fas fa-info-circle mr-2"></i>
                最新のお知らせ
              </h2>
            </div>
            <div class="card-body">
              @forelse ($information as $item)
                <div class="border-bottom pb-2 mb-3">
                  <div class="text-muted small">
                    {{ $item->published_at?->format('Y/m/d') }}
                  </div>
                  <div class="font-weight-bold">{{ $item->title }}</div>
                  <div class="text-muted small">
                    {{ \Illuminate\Support\Str::limit(strip_tags($item->content ?? ''), 90) }}
                  </div>
                </div>
              @empty
                <p class="text-muted mb-0">現在、公開中のお知らせはありません。</p>
              @endforelse
            </div>
          </div>

          <div class="card">
            <div class="card-body text-center">
              <p class="mb-3">
                支援団体の方はログインすると、日報・支援計画・お知らせの詳細を確認できます。
              </p>
              <a href="{{ route('login') }}" class="btn btn-primary btn-block">
                ログイン
              </a>
              @if (config('signup.enabled'))
                <a href="{{ route('signup.create') }}" class="btn btn-outline-secondary btn-block">
                  新規利用登録
                </a>
              @endif
              <a href="{{ route('terms') }}" class="btn btn-link btn-sm">
                利用規約を確認する
              </a>
            </div>
          </div>

        </div>

      </div>
    </div>
  </section>

@endsection
