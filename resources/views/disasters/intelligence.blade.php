@extends('layouts.map')

@section('title', '専門支援情報 | ' . $disaster->name)

@section('content')
    <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-white border-bottom" style="height: 64px;">
        <div class="d-flex align-items-center">
            <a href="{{ route('disasters.show', $disaster->id) }}" class="btn btn-sm btn-outline-secondary mr-3">
                <i class="fas fa-arrow-left"></i> 災害地図に戻る
            </a>
            <div>
                <div class="font-weight-bold">{{ $disaster->name }} 専門支援情報</div>
                <div class="text-muted small">道路規制・災害廃棄物仮置場（MapLibre GL JS）</div>
            </div>
        </div>
        <div class="text-muted small text-right">
            AI抽出を含むため、必ず一次資料を確認してください。
        </div>
    </div>

    <x-disaster-intelligence-map :disaster-id="$disaster->id" height="calc(100vh - 64px)" />
@endsection
