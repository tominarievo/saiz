@extends('layouts.map')

@section('title', 'データ出典 | ' . $disaster->name)

@section('content')
    <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-white border-bottom" style="height: 64px;">
        <div class="d-flex align-items-center">
            <a href="{{ route('disasters.show', $disaster->id) }}" class="btn btn-sm btn-outline-secondary mr-3">
                <i class="fas fa-arrow-left"></i> 災害地図に戻る
            </a>
            <div>
                <div class="font-weight-bold">{{ $disaster->name }} データ出典</div>
                <div class="text-muted small">自動収集した情報の出典と、カテゴリごとのデータ収集日です。二次利用時は各出典の利用条件を確認してください。</div>
            </div>
        </div>
        <div class="d-flex align-items-center">
            @auth
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.collected_information.index', ['disaster_id' => $disaster->id]) }}" class="btn btn-sm btn-outline-danger mr-2">
                        <i class="fas fa-trash-alt"></i> 収集情報の管理
                    </a>
                @endif
            @endauth
            <a href="{{ route('disasters.intelligence', $disaster->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-truck-ramp-box"></i> 専門支援情報
            </a>
        </div>
    </div>

    <div style="height: calc(100vh - 64px); overflow-y: auto; background: #f4f6f9;">
        <div class="container-fluid py-4" style="max-width: 1200px;">
            @php
                $staleBefore = now()->subMonth();
            @endphp

            <div class="row">
                @foreach ($categories as $category => $label)
                    <div class="col-6 col-md-4 col-xl-2 mb-3">
                        <div class="card h-100 mb-0">
                            <div class="card-body py-3">
                                <div class="text-muted small">{{ $label }}</div>
                                <div class="font-weight-bold">
                                    @if ($latestCollectedAt[$category] ?? null)
                                        {{ $latestCollectedAt[$category]->format('Y/m/d H:i') }}
                                    @else
                                        未収集
                                    @endif
                                </div>
                                <div class="text-muted small">
                                    {{ ($groupedSources->get($category) ?? collect())->count() }}件の出典
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @foreach ($categories as $category => $label)
                @php
                    $sources = $groupedSources->get($category) ?? collect();
                @endphp
                <div class="card mt-3">
                    <div class="card-header">
                        <strong>{{ $label }}</strong>
                        <span class="text-muted small ml-2">
                            最終収集日:
                            {{ ($latestCollectedAt[$category] ?? null) ? $latestCollectedAt[$category]->format('Y/m/d H:i') : '未収集' }}
                        </span>
                    </div>
                    <div class="card-body p-0">
                        @if ($sources->isEmpty())
                            <p class="text-muted small mb-0 p-3">登録されている出典はありません。</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                    <tr>
                                        <th style="width: 16%">出典</th>
                                        <th>タイトル</th>
                                        <th style="width: 14%">最終収集日</th>
                                        <th style="width: 10%">公表日</th>
                                        <th style="width: 16%">利用条件</th>
                                        <th style="width: 24%">URL</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($sources as $source)
                                        @php($license = \App\Support\SourceLicense::forUrl($source->source_url))
                                        @php($retrievalUrl = is_array($source->metadata) ? ($source->metadata['retrieval_url'] ?? null) : null)
                                        <tr>
                                            <td>{{ $source->source_name ?: '-' }}</td>
                                            <td>{{ $source->title ?: '-' }}</td>
                                            <td class="text-nowrap">
                                                {{ $source->collected_at ? $source->collected_at->format('Y/m/d H:i') : '-' }}
                                                @if ($source->collected_at && $source->collected_at->lt($staleBefore))
                                                    <span class="badge badge-warning ml-1">1か月以上経過</span>
                                                @endif
                                            </td>
                                            <td>{{ $source->published_at ? $source->published_at->format('Y/m/d') : '-' }}</td>
                                            <td class="small">
                                                @if ($license['url'])
                                                    <a href="{{ $license['url'] }}" target="_blank" rel="noopener noreferrer">
                                                        {{ $license['name'] }}
                                                    </a>
                                                @else
                                                    {{ $license['name'] }}
                                                @endif
                                            </td>
                                            <td class="text-break">
                                                @if ($source->source_url)
                                                    <a href="{{ $source->source_url }}" target="_blank" rel="noopener noreferrer">
                                                        {{ $source->source_url }}
                                                    </a>
                                                    @if ($retrievalUrl && $retrievalUrl !== $source->source_url)
                                                        <div class="small text-muted">
                                                            <a href="{{ $retrievalUrl }}" target="_blank" rel="noopener noreferrer">PDF</a>
                                                        </div>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="card mt-4">
                <div class="card-header">
                    <strong>AI収集元の探索履歴</strong>
                    <span class="text-muted small ml-2">AIが探索した収集候補の一覧です。</span>
                </div>
                <div class="card-body p-0">
                    @if ($intelligenceSources->isEmpty())
                        <p class="text-muted small mb-0 p-3">登録されているAI収集元はありません。</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                <tr>
                                    <th style="width: 12%">カテゴリ</th>
                                    <th style="width: 20%">発信組織</th>
                                    <th style="width: 8%">信頼度</th>
                                    <th style="width: 12%">発見日</th>
                                    <th style="width: 12%">最終同期日</th>
                                    <th style="width: 16%">利用条件</th>
                                    <th>URL</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($intelligenceSources as $source)
                                    @php($sourcePageUrl = is_array($source->metadata) ? ($source->metadata['source_page_url'] ?? null) : null)
                                    @php($license = \App\Support\SourceLicense::forUrl($sourcePageUrl ?: $source->url))
                                    <tr>
                                        <td>{{ $categories[$source->type] ?? $source->type }}</td>
                                        <td>{{ $source->source_org ?: '-' }}</td>
                                        <td>{{ $source->credibility_rank ?: '-' }}</td>
                                        <td>{{ $source->discovered_at ? $source->discovered_at->format('Y/m/d') : '-' }}</td>
                                        <td class="text-nowrap">
                                            {{ $source->last_synced_at ? $source->last_synced_at->format('Y/m/d') : '-' }}
                                            @if ($source->last_synced_at && $source->last_synced_at->lt($staleBefore))
                                                <span class="badge badge-warning ml-1">1か月以上経過</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if ($license['url'])
                                                <a href="{{ $license['url'] }}" target="_blank" rel="noopener noreferrer">
                                                    {{ $license['name'] }}
                                                </a>
                                            @else
                                                {{ $license['name'] }}
                                            @endif
                                        </td>
                                        <td class="text-break">
                                            <a href="{{ $sourcePageUrl ?: $source->url }}" target="_blank" rel="noopener noreferrer">
                                                {{ $sourcePageUrl ?: $source->url }}
                                            </a>
                                            @if ($sourcePageUrl && $sourcePageUrl !== $source->url)
                                                <div class="small text-muted">
                                                    <a href="{{ $source->url }}" target="_blank" rel="noopener noreferrer">PDF</a>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
