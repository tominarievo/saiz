@extends('layouts.map')

@section('title', $disaster->name . ' | 災害情報')

@push('styles')
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <style>
    html,
    body {
      height: 100%;
    }

    body.map-app-body {
      overflow: hidden;
      background: #dee2e6;
    }

    .map-app {
      position: fixed;
      inset: 0;
      overflow: hidden;
      background: #dee2e6;
    }

    .map-app-map {
      position: absolute;
      inset: 0;
      z-index: 0;
    }

    #disaster-map:focus,
    #disaster-map svg:focus,
    #disaster-map .leaflet-interactive:focus {
      outline: none;
    }

    #disaster-map .leaflet-overlay-pane path:focus {
      outline: none;
      stroke: #dc3545;
      stroke-width: 4;
      stroke-dasharray: none;
      fill: none;
    }

    .map-app-empty {
      position: absolute;
      top: 50%;
      left: 50%;
      z-index: 5;
      width: min(90%, 28rem);
      padding: 1rem 1.25rem;
      transform: translate(-50%, -50%);
      background: #fff;
      border-radius: 0.5rem;
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.18);
      text-align: center;
    }

    .map-app-topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1200;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      height: 56px;
      padding: 0 0.75rem;
      background: rgba(255, 255, 255, 0.97);
      box-shadow: 0 1px 8px rgba(0, 0, 0, 0.16);
      backdrop-filter: blur(6px);
    }

    .map-app-icon-button,
    .map-app-sidebar-close {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      padding: 0;
      color: #1f3c88;
      background: transparent;
      border: 0;
      border-radius: 0.375rem;
      cursor: pointer;
      flex-shrink: 0;
    }

    .map-app-icon-button:hover,
    .map-app-sidebar-close:hover,
    .map-app-icon-button:focus-visible,
    .map-app-sidebar-close:focus-visible {
      background: #eef2f9;
      outline: none;
    }

    .map-app-brand {
      display: inline-flex;
      align-items: center;
      flex-shrink: 0;
    }

    .map-app-brand img {
      width: 34px;
      height: 34px;
      object-fit: contain;
      opacity: 0.9;
    }

    .map-app-title {
      flex: 1;
      min-width: 0;
      line-height: 1.2;
    }

    .map-app-title-name {
      overflow: hidden;
      font-size: 0.98rem;
      font-weight: 700;
      color: #212529;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .map-app-title-meta {
      margin-top: 0.15rem;
      overflow: hidden;
      color: #6c757d;
      font-size: 0.75rem;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .map-app-topbar-actions {
      display: flex;
      align-items: center;
      gap: 0.15rem;
      flex-shrink: 0;
    }

    .map-app-bar-link {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.4rem 0.55rem;
      color: #1f3c88;
      background: transparent;
      border: 0;
      border-radius: 0.375rem;
      font-size: 0.85rem;
      text-decoration: none;
      white-space: nowrap;
      cursor: pointer;
    }

    .map-app-bar-link:hover,
    .map-app-bar-link:focus-visible {
      color: #12285f;
      background: #eef2f9;
      text-decoration: none;
      outline: none;
    }

    .map-app-sidebar {
      position: fixed;
      top: 56px;
      bottom: 0;
      left: 0;
      z-index: 1100;
      display: flex;
      flex-direction: column;
      width: 380px;
      max-width: 90vw;
      background: #fff;
      box-shadow: 3px 0 16px rgba(0, 0, 0, 0.2);
      transform: translateX(-105%);
      transition: transform 0.25s ease;
    }

    .map-app-sidebar.is-open {
      transform: translateX(0);
    }

    .map-app-sidebar-resizer {
      position: absolute;
      top: 0;
      right: -6px;
      width: 6px;
      height: 100%;
      z-index: 2;
      cursor: col-resize;
      touch-action: none;
    }

    .map-app-sidebar-resizer::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      width: 2px;
      height: 44px;
      margin-top: -22px;
      background: #cfd4da;
      border-radius: 2px;
      transition: background 0.15s ease;
    }

    .map-app-sidebar-resizer:hover::after,
    .map-app-sidebar-resizer.is-resizing::after,
    .map-app-sidebar-resizer:focus-visible::after {
      background: #17a2b8;
    }

    body.map-app-resizing {
      cursor: col-resize;
      user-select: none;
    }

    .shelter-marker span {
      display: block;
      width: 14px;
      height: 14px;
      background: #17a2b8;
      border: 2px solid #fff;
      border-radius: 50%;
      box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.25), 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    .shelter-marker-emergency span {
      background: #fd7e14;
    }

    .shelter-marker-active span {
      width: 18px;
      height: 18px;
      background: #dc3545;
      border: 3px solid #fff;
      box-shadow: 0 0 0 2px #dc3545, 0 0 0 6px rgba(220, 53, 69, 0.22);
      animation: shelter-active-pulse 1.8s ease-out infinite;
    }

    @keyframes shelter-active-pulse {
      0% {
        box-shadow: 0 0 0 2px #dc3545, 0 0 0 2px rgba(220, 53, 69, 0.35);
      }
      70% {
        box-shadow: 0 0 0 2px #dc3545, 0 0 0 12px rgba(220, 53, 69, 0);
      }
      100% {
        box-shadow: 0 0 0 2px #dc3545, 0 0 0 2px rgba(220, 53, 69, 0);
      }
    }

    .volunteer-center-marker span {
      display: block;
      width: 16px;
      height: 16px;
      background: #28a745;
      border: 2px solid #fff;
      border-radius: 4px;
      box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.25), 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    .map-popup-navigation {
      display: inline-block;
      margin-top: 0.55rem;
      padding: 0.35rem 0.8rem;
      background: #007bff;
      color: #fff !important;
      border-radius: 4px;
      font-weight: 600;
      text-decoration: none !important;
    }

    .map-popup-navigation:hover,
    .map-popup-navigation:focus {
      background: #0056b3;
      color: #fff !important;
    }

    .map-app-sidebar-header {
      position: relative;
      flex-shrink: 0;
      padding: 1rem 1.1rem 0.85rem;
      border-bottom: 1px solid #e9ecef;
    }

    .map-app-sidebar-eyebrow {
      color: #17a2b8;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.05em;
    }

    .map-app-sidebar-title {
      margin: 0.25rem 0 0.5rem;
      padding-right: 2rem;
      font-size: 1.25rem;
      font-weight: 700;
      line-height: 1.35;
    }

    .map-app-sidebar-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.3rem;
    }

    .map-app-sidebar-close {
      position: absolute;
      top: 0.65rem;
      right: 0.55rem;
    }

    .map-app-tabs {
      display: flex;
      flex-shrink: 0;
      overflow-x: auto;
      background: #f8f9fa;
      border-bottom: 1px solid #e9ecef;
      scrollbar-width: none;
    }

    .map-app-tabs::-webkit-scrollbar {
      display: none;
    }

    .map-app-tab {
      flex: 0 0 auto;
      min-width: 3.45rem;
      padding: 0.7rem 0.3rem;
      color: #6c757d;
      background: transparent;
      border: 0;
      border-bottom: 2px solid transparent;
      font-size: 0.8rem;
      font-weight: 600;
      white-space: nowrap;
      cursor: pointer;
    }

    .map-app-tab:hover,
    .map-app-tab:focus-visible {
      color: #1f3c88;
      outline: none;
    }

    .map-app-tab.is-active {
      color: #1f3c88;
      background: #fff;
      border-bottom-color: #17a2b8;
    }

    .map-app-tab-count {
      display: inline-block;
      min-width: 1.25rem;
      margin-left: 0.2rem;
      padding: 0 0.3rem;
      color: #fff;
      background: #17a2b8;
      border-radius: 999px;
      font-size: 0.7rem;
      line-height: 1.25rem;
      text-align: center;
    }

    .map-app-sidebar-body {
      flex: 1;
      overflow-y: auto;
      overscroll-behavior: contain;
      -webkit-overflow-scrolling: touch;
    }

    .map-app-panel {
      padding: 1rem 1.1rem 1.5rem;
    }

    .map-app-panel[hidden] {
      display: none;
    }

    .map-app-detail-list {
      margin: 0;
    }

    .map-app-detail-row {
      display: grid;
      grid-template-columns: 5.5rem 1fr;
      gap: 0.65rem;
      padding: 0.55rem 0;
      border-bottom: 1px solid #f1f3f5;
    }

    .map-app-detail-row dt {
      margin: 0;
      color: #6c757d;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .map-app-detail-row dd {
      margin: 0;
      font-size: 0.9rem;
      line-height: 1.5;
    }

    .map-app-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-top: 1rem;
    }

    .map-app-news-item {
      padding: 0.75rem 0;
      border-bottom: 1px solid #f1f3f5;
    }

    .map-app-news-item:first-child {
      padding-top: 0;
    }

    .map-app-news-item p {
      margin: 0 0 0.4rem;
      font-size: 0.88rem;
      line-height: 1.55;
    }

    .map-app-news-meta {
      margin-bottom: 0.25rem;
      color: #6c757d;
      font-size: 0.7rem;
    }

    .map-app-news-item a {
      font-size: 0.78rem;
      word-break: break-all;
    }

    .map-layer-title {
      margin-bottom: 0.5rem;
      font-weight: 700;
    }

    .map-layer-control details {
      margin-bottom: 0.5rem;
    }

    .map-layer-control summary {
      color: #1f3c88;
      font-weight: 600;
      cursor: pointer;
    }

    .map-layer-option {
      display: block;
      margin: 0.35rem 0 0.35rem 1rem;
      font-size: 0.88rem;
      cursor: pointer;
    }

    .map-layer-status {
      margin-top: 0.35rem;
      color: #6c757d;
      font-size: 0.78rem;
      line-height: 1.4;
    }

    .map-layer-note {
      margin: 0.75rem 0 0;
      color: #6c757d;
      font-size: 0.75rem;
      line-height: 1.5;
    }

    .support-panel {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }

    .support-section-header {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      margin-bottom: 0.5rem;
      padding-bottom: 0.35rem;
      border-bottom: 1px solid #e9ecef;
    }

    .support-section-header strong {
      color: #1f3c88;
      font-size: 0.9rem;
    }

    .support-section-header span {
      color: #6c757d;
      font-size: 0.72rem;
    }

    .support-list {
      display: flex;
      flex-direction: column;
      gap: 0.45rem;
    }

    .support-empty {
      margin: 0;
      color: #6c757d;
      font-size: 0.78rem;
    }

    .support-item {
      display: block;
      width: 100%;
      padding: 0.55rem 0.6rem;
      border: 1px solid #e9ecef;
      border-radius: 0.4rem;
      background: #fff;
      color: #212529;
      text-align: left;
      cursor: pointer;
    }

    .support-item:hover,
    .support-item:focus-visible {
      border-color: #17a2b8;
      background: #f1fbfd;
      outline: none;
    }

    .support-item-name {
      display: block;
      margin-top: 0.2rem;
      font-size: 0.82rem;
      font-weight: 700;
      line-height: 1.35;
    }

    .support-item-meta {
      display: block;
      margin-top: 0.15rem;
      color: #6c757d;
      font-size: 0.72rem;
      line-height: 1.4;
    }

    .support-badge {
      display: inline-block;
      padding: 0.08rem 0.38rem;
      border-radius: 0.25rem;
      color: #fff;
      font-size: 0.66rem;
      font-weight: 700;
      line-height: 1.4;
    }

    .support-meeting {
      padding: 0.65rem 0.7rem;
      border: 1px solid #e9ecef;
      border-radius: 0.4rem;
      background: #fff;
    }

    .support-meeting-title {
      display: block;
      color: #1f3c88;
      font-size: 0.82rem;
      font-weight: 700;
      line-height: 1.4;
    }

    .support-meeting-block {
      margin-top: 0.45rem;
      font-size: 0.74rem;
      line-height: 1.45;
    }

    .support-meeting-block strong {
      display: block;
      margin-bottom: 0.1rem;
      color: #495057;
    }

    .support-meeting-block ul {
      margin: 0;
      padding-left: 1.1rem;
    }

    .support-meeting-block li + li {
      margin-top: 0.1rem;
    }

    .support-meeting-source {
      margin-top: 0.45rem;
      padding-top: 0.4rem;
      border-top: 1px solid #f1f3f5;
      color: #6c757d;
      font-size: 0.7rem;
      line-height: 1.45;
    }

    .population-municipality + .population-municipality {
      margin-top: 1.25rem;
      padding-top: 1rem;
      border-top: 1px solid #e9ecef;
    }

    .population-municipality > summary {
      list-style: none;
      cursor: pointer;
    }

    .population-municipality > summary::-webkit-details-marker {
      display: none;
    }

    .population-municipality > summary::before {
      content: '▶';
      flex-shrink: 0;
      color: #1f3c88;
      font-size: 0.65rem;
      transition: transform 0.15s ease;
    }

    .population-municipality[open] > summary::before {
      transform: rotate(90deg);
    }

    .population-municipality > summary:focus-visible {
      outline: 2px solid #17a2b8;
      outline-offset: 2px;
    }

    .population-header {
      display: flex;
      align-items: baseline;
      gap: 0.5rem;
    }

    .population-header .population-title {
      margin: 0;
      font-size: 1.02rem;
      font-weight: 700;
    }

    .population-header .population-date {
      margin-left: auto;
      color: #6c757d;
      font-size: 0.7rem;
      white-space: nowrap;
    }

    .population-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.45rem;
      margin: 0.7rem 0;
    }

    .population-stat {
      padding: 0.5rem 0.6rem;
      background: #f8f9fa;
      border-radius: 0.375rem;
    }

    .population-stat-label {
      display: block;
      color: #6c757d;
      font-size: 0.7rem;
    }

    .population-stat-value {
      font-size: 1rem;
      font-weight: 700;
      font-variant-numeric: tabular-nums;
    }

    .population-source {
      margin: 0.25rem 0 0;
      color: #6c757d;
      font-size: 0.7rem;
      line-height: 1.4;
      word-break: break-all;
    }

    .population-section-title {
      margin: 1rem 0 0.45rem;
      font-size: 0.88rem;
      font-weight: 700;
    }

    .population-section-title small {
      color: #6c757d;
      font-size: 0.7rem;
      font-weight: 400;
    }

    .population-age-list {
      display: flex;
      flex-direction: column;
      gap: 0.22rem;
    }

    .population-age-row {
      display: grid;
      grid-template-columns: 4.2rem 1fr 3.2rem;
      gap: 0.4rem;
      align-items: center;
      font-size: 0.76rem;
    }

    .population-age-label {
      color: #495057;
      white-space: nowrap;
    }

    .population-age-bar {
      height: 0.55rem;
      overflow: hidden;
      background: #e9ecef;
      border-radius: 999px;
    }

    .population-age-bar span {
      display: block;
      height: 100%;
      background: #17a2b8;
      border-radius: 999px;
    }

    .population-age-total {
      text-align: right;
      font-variant-numeric: tabular-nums;
    }

    .population-age-foreigners {
      grid-column: 2 / 4;
      margin-top: -0.15rem;
      color: #6c757d;
      font-size: 0.68rem;
      text-align: right;
    }

    .population-small-areas {
      margin-top: 1rem;
    }

    .population-small-areas summary {
      color: #1f3c88;
      font-size: 0.84rem;
      font-weight: 600;
      cursor: pointer;
    }

    .population-table-wrap {
      max-height: 260px;
      margin-top: 0.5rem;
      overflow: auto;
      border: 1px solid #e9ecef;
      border-radius: 0.375rem;
    }

    .population-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.74rem;
    }

    .population-table th,
    .population-table td {
      padding: 0.35rem 0.45rem;
      border-bottom: 1px solid #f1f3f5;
      white-space: nowrap;
    }

    .population-table th {
      position: sticky;
      top: 0;
      background: #f8f9fa;
      text-align: left;
    }

    .population-table td.number {
      text-align: right;
      font-variant-numeric: tabular-nums;
    }

    .population-empty {
      color: #6c757d;
      font-size: 0.85rem;
      line-height: 1.5;
    }

    .population-mesh-note {
      margin: 0.75rem 0 0;
      color: #6c757d;
      font-size: 0.7rem;
      line-height: 1.4;
    }

    .leaflet-top.leaflet-right {
      top: 64px;
    }

    @media (max-width: 767.98px) {
      .map-app-topbar {
        height: 52px;
        gap: 0.5rem;
        padding: 0 0.5rem;
      }

      .map-app-sidebar {
        top: 52px;
        width: 88vw;
      }

      .map-app-title-meta {
        display: none;
      }

      .map-app-topbar-actions .map-app-bar-link span {
        display: none;
      }

      .leaflet-top.leaflet-right {
        top: 60px;
      }
    }
  </style>
@endpush

@section('content')

  <div class="map-app" id="map-app">

    <div id="disaster-map" class="map-app-map"></div>

    @if ($disaster->localGovernments->isEmpty())
      <div class="map-app-empty">
        <p class="mb-0">市町村が登録されていないため、地図を表示できません。</p>
      </div>
    @endif

    <header class="map-app-topbar">
      <button type="button" class="map-app-icon-button" id="sidebar-toggle" aria-controls="map-sidebar" aria-expanded="true" aria-label="メニューを開閉">
        <i class="fas fa-bars"></i>
      </button>

      <a href="{{ url('/') }}" class="map-app-brand" aria-label="トップへ">
        <img src="{{ asset('img/logo2.png') }}" alt="災図">
      </a>

      <div class="map-app-title">
        <div class="map-app-title-name">{{ $disaster->name }}</div>
        <div class="map-app-title-meta">
          @if (filled($disaster->event_date))
            {{ $disaster->event_date }}
          @endif
          @if (filled($disaster->npo_col_2))
            <span class="mx-1">/</span>{{ $disaster->npo_col_2 }}
          @endif
        </div>
      </div>

      <div class="map-app-topbar-actions">
        @auth
          @if (auth()->user()->isAdmin())
            <a href="{{ route('disasters.index') }}" class="map-app-bar-link">
              <i class="fas fa-list"></i><span>災害一覧</span>
            </a>
          @endif
          <a href="{{ route('home') }}" class="map-app-bar-link">
            <i class="fas fa-home"></i><span>ホーム</span>
          </a>
          <form action="{{ route('logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="map-app-bar-link">
              <i class="fas fa-sign-out-alt"></i><span>ログアウト</span>
            </button>
          </form>
        @else
          <a href="{{ route('terms') }}" class="map-app-bar-link">
            <i class="fas fa-file-alt"></i><span>利用規約</span>
          </a>
          <a href="{{ route('login') }}" class="map-app-bar-link">
            <i class="fas fa-sign-in-alt"></i><span>ログイン</span>
          </a>
          @if (config('signup.enabled'))
            <a href="{{ route('signup.create') }}" class="btn btn-primary btn-sm">
              新規登録
            </a>
          @endif
        @endauth
      </div>
    </header>

    <aside class="map-app-sidebar" id="map-sidebar" aria-label="災害情報メニュー">
      <div class="map-app-sidebar-header">
        <div class="map-app-sidebar-eyebrow">災害情報</div>
        <h1 class="map-app-sidebar-title">{{ $disaster->name }}</h1>
        <div class="map-app-sidebar-meta">
          @if ($disaster->is_catastrophic_disaster)
            <span class="badge badge-danger">激甚災害</span>
          @endif
          @if (filled($disaster->npo_col_2))
            <span class="badge badge-light border">{{ $disaster->npo_col_2 }}</span>
          @endif
          @if (filled($disaster->event_date))
            <span class="badge badge-light border">{{ $disaster->event_date }}</span>
          @endif
        </div>
        <button type="button" class="map-app-sidebar-close" id="sidebar-close" aria-label="メニューを閉じる">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="map-app-tabs" role="tablist" aria-label="表示切替">
        <button type="button" class="map-app-tab is-active" data-map-panel="detail" role="tab" aria-selected="true">
          災害情報
        </button>
        <button type="button" class="map-app-tab" data-map-panel="news" role="tab" aria-selected="false">
          最新情報
          @if ($disaster->informationLinks->isNotEmpty())
            <span class="map-app-tab-count">{{ $disaster->informationLinks->count() }}</span>
          @endif
        </button>
        <button type="button" class="map-app-tab" data-map-panel="population" role="tab" aria-selected="false">
          人口
        </button>
        <button type="button" class="map-app-tab" data-map-panel="support" role="tab" aria-selected="false">
          支援
          <span id="support-tab-count" class="map-app-tab-count" hidden></span>
        </button>
        <button type="button" class="map-app-tab" data-map-panel="layers" role="tab" aria-selected="false">
          レイヤー
        </button>
      </div>

      <div class="map-app-sidebar-body">

        <section class="map-app-panel" data-map-panel-content="detail" role="tabpanel">
          <dl class="map-app-detail-list">
            <div class="map-app-detail-row">
              <dt>年度</dt>
              <dd>{{ filled($disaster->npo_col_2) ? $disaster->npo_col_2 : '-' }}</dd>
            </div>
            <div class="map-app-detail-row">
              <dt>発生年月日</dt>
              <dd>{{ filled($disaster->event_date) ? $disaster->event_date : '-' }}</dd>
            </div>
            <div class="map-app-detail-row">
              <dt>災害種別</dt>
              <dd>
                @forelse ($disaster->disasterTypes as $disasterType)
                  <span class="badge badge-light border">{{ $disasterType->name }}</span>
                @empty
                  <span class="text-muted">未登録</span>
                @endforelse
              </dd>
            </div>
            <div class="map-app-detail-row">
              <dt>市町村</dt>
              <dd>
                @forelse ($disaster->localGovernments as $localGovernment)
                  <span class="badge badge-light border">
                    {{ $localGovernment->prefecture->name }} {{ $localGovernment->name }}
                  </span>
                @empty
                  <span class="text-muted">未登録</span>
                @endforelse
              </dd>
            </div>
            <div class="map-app-detail-row">
              <dt>備考</dt>
              <dd>
                @if (filled($disaster->npo_col_3))
                  {!! nl2br(e($disaster->npo_col_3)) !!}
                @else
                  <span class="text-muted">-</span>
                @endif
              </dd>
            </div>
          </dl>

          <div class="map-app-actions">
            <a href="{{ route('disasters.intelligence', ['disaster' => $disaster->id]) }}" class="btn btn-outline-primary">
              <i class="fas fa-truck-ramp-box"></i> 専門支援情報
            </a>
            @auth
              @if (auth()->user()->isAdmin())
                <a href="{{ route('disasters.edit', ['disaster' => $disaster->id]) }}" class="btn btn-info">
                  <i class="fas fa-edit"></i> 編集
                </a>
                <a href="{{ route('disasters.index') }}" class="btn btn-default">
                  一覧に戻る
                </a>
              @else
                <a href="{{ route('home') }}" class="btn btn-default">
                  ホームに戻る
                </a>
              @endif
            @else
              <a href="{{ route('login') }}" class="btn btn-primary">
                ログイン
              </a>
              @if (config('signup.enabled'))
                <a href="{{ route('signup.create') }}" class="btn btn-outline-secondary">
                  新規利用登録
                </a>
              @endif
              <a href="{{ url('/') }}" class="btn btn-default">
                トップに戻る
              </a>
            @endauth
          </div>
        </section>

        <section class="map-app-panel" data-map-panel-content="news" role="tabpanel" hidden>
          @forelse ($disaster->informationLinks as $informationLink)
            <article class="map-app-news-item">
              @if ($informationLink->fetched_at)
                <div class="map-app-news-meta">取得日時: {{ $informationLink->fetched_at->format('Y/m/d H:i') }}</div>
              @elseif ($informationLink->updated_at)
                <div class="map-app-news-meta">登録日時: {{ $informationLink->updated_at->format('Y/m/d H:i') }}</div>
              @endif
              <p>{{ $informationLink->description }}</p>
              <a href="{{ $informationLink->url }}" target="_blank" rel="noopener noreferrer">
                {{ $informationLink->url }}
              </a>
            </article>
          @empty
            <p class="text-muted mb-0">最新情報は登録されていません。</p>
          @endforelse
        </section>

        <section class="map-app-panel" data-map-panel-content="population" role="tabpanel" hidden>
          <div id="population-content">
            <p class="population-empty mb-0">人口データを読み込んでいます...</p>
          </div>
        </section>

        <section class="map-app-panel" data-map-panel-content="support" role="tabpanel" hidden>
          <div class="support-panel">
            <div>
              <div class="support-section-header">
                <strong>道路規制</strong>
                <span id="support-road-count">読み込み中...</span>
              </div>
              <div id="support-road-list" class="support-list">
                <p class="support-empty">道路規制を読み込んでいます...</p>
              </div>
            </div>

            <div>
              <div class="support-section-header">
                <strong>災害廃棄物・仮置場</strong>
                <span id="support-waste-count">読み込み中...</span>
              </div>
              <div id="support-waste-list" class="support-list">
                <p class="support-empty">仮置場情報を読み込んでいます...</p>
              </div>
            </div>

            <div>
              <div class="support-section-header">
                <strong>議事録サマリ</strong>
                <span id="support-meeting-count">読み込み中...</span>
              </div>
              <div id="support-meeting-list" class="support-list">
                <p class="support-empty">議事録を読み込んでいます...</p>
              </div>
            </div>
          </div>
        </section>

        <section class="map-app-panel" data-map-panel-content="layers" role="tabpanel" hidden>
          <div class="map-layer-control">
            <div class="map-layer-title">表示レイヤー</div>

            <details open>
              <summary>行政界</summary>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="municipalities" checked>
                現在の市町村境
              </label>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="small_areas">
                小区域界
              </label>
            </details>

            <details>
              <summary>避難所</summary>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="shelters">
                指定避難所・指定緊急避難場所
              </label>
              <label class="map-layer-option" style="margin-left: 1.1rem;">
                <input type="checkbox" id="shelter-active-only">
                開設中のみ表示
              </label>
              <div class="map-layer-status">赤い波紋: 開設中 / 青: 指定避難所 / 橙: 指定緊急避難場所</div>
            </details>

            <details>
              <summary>ボランティア</summary>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="volunteer_centers">
                ボランティアセンター
              </label>
            </details>

            <details>
              <summary>専門支援</summary>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="roads">
                道路規制
              </label>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="waste_sites">
                災害廃棄物・仮置場
              </label>
              <div class="map-layer-status">赤: 全面通行止 / 橙: 片側交互 / 紫: 大型・重量規制 / 緑: 仮置場</div>
            </details>

            <details>
              <summary>学区</summary>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="elementary">
                小学校区
              </label>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="junior_high">
                中学校区
              </label>
            </details>

            <details open>
              <summary>合併前行政界</summary>
              @if ($historicalDates->isEmpty())
                <div class="map-layer-status">データ未取込</div>
              @else
                <label class="map-layer-option">
                  <input type="checkbox" class="map-layer-toggle" data-layer="historical">
                  表示
                </label>
                <label class="map-layer-option" style="margin-left: 0;">
                  基準日
                  <select id="historical-date" class="form-control form-control-sm mt-1">
                    @foreach ($historicalDates as $historicalDate)
                      <option value="{{ $historicalDate->format('Y-m-d') }}">
                        {{ $historicalDate->format('Y-m-d') }}
                      </option>
                    @endforeach
                  </select>
                </label>
                <label class="map-layer-option" style="margin-left: 0;">
                  旧市町村を選択
                  <select id="historical-name" class="form-control form-control-sm mt-1" disabled>
                    <option value="">基準日を選択してください</option>
                  </select>
                </label>
              @endif
            </details>

            <div id="layer-status" class="map-layer-status"></div>
            <div id="disaster-map-status" class="map-layer-status"></div>
          </div>

          <p class="map-layer-note">
            選択された市町村の行政区域を表示しています。学区は参考情報であり、公式な通学区域を示すものではありません。合併前行政界は基準日時点の参考情報で、区域にカーソルを合わせると旧市町村名を表示します。
          </p>
        </section>

      </div>

      <div class="map-app-sidebar-resizer" id="sidebar-resizer" role="separator" aria-orientation="vertical" aria-label="サイドメニューの幅を変更" tabindex="0"></div>
    </aside>
  </div>

@endsection


@push('scripts')
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://unpkg.com/topojson-client@3/dist/topojson-client.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar = document.getElementById('map-sidebar');
      const sidebarToggle = document.getElementById('sidebar-toggle');
      const sidebarClose = document.getElementById('sidebar-close');
      const sidebarStorageKey = 'disaster-map-sidebar-open';

      function setSidebarOpen(open, persist) {
        if (!sidebar) {
          return;
        }

        sidebar.classList.toggle('is-open', open);
        sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');

        if (typeof sidebar.toggleAttribute === 'function') {
          sidebar.toggleAttribute('inert', !open);
        }

        if (sidebarToggle) {
          sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        if (persist) {
          try {
            window.localStorage.setItem(sidebarStorageKey, open ? '1' : '0');
          } catch (error) {
          }
        }
      }

      if (sidebar && sidebarToggle) {
        let sidebarOpen = window.matchMedia('(min-width: 992px)').matches;

        try {
          const storedSidebar = window.localStorage.getItem(sidebarStorageKey);

          if (storedSidebar === '1' || storedSidebar === '0') {
            sidebarOpen = storedSidebar === '1';
          }
        } catch (error) {
        }

        setSidebarOpen(sidebarOpen, false);

        sidebarToggle.addEventListener('click', function () {
          setSidebarOpen(!sidebar.classList.contains('is-open'), true);
        });

        if (sidebarClose) {
          sidebarClose.addEventListener('click', function () {
            setSidebarOpen(false, true);
          });
        }
      }

      document.querySelectorAll('[data-map-panel]').forEach(function (tab) {
        tab.addEventListener('click', function () {
          const panel = this.dataset.mapPanel;

          document.querySelectorAll('[data-map-panel]').forEach(function (item) {
            const active = item === tab;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', active ? 'true' : 'false');
          });

          document.querySelectorAll('[data-map-panel-content]').forEach(function (content) {
            content.hidden = content.dataset.mapPanelContent !== panel;
          });

          if (panel === 'population') {
            loadPopulationData();
          } else if (panel === 'support') {
            loadSupportPanelData();
          }
        });
      });

      const sidebarResizer = document.getElementById('sidebar-resizer');
      const sidebarWidthStorageKey = 'disaster-map-sidebar-width';
      const minSidebarWidth = 280;

      function maxSidebarWidth() {
        return Math.max(minSidebarWidth, Math.round(window.innerWidth * 0.9));
      }

      function clampSidebarWidth(width) {
        return Math.max(minSidebarWidth, Math.min(Math.round(width), maxSidebarWidth()));
      }

      function saveSidebarWidth() {
        if (!sidebar) {
          return;
        }

        try {
          window.localStorage.setItem(
            sidebarWidthStorageKey,
            String(Math.round(sidebar.getBoundingClientRect().width))
          );
        } catch (error) {
        }
      }

      if (sidebar && sidebarResizer) {
        try {
          const storedSidebarWidth = Number(window.localStorage.getItem(sidebarWidthStorageKey));

          if (storedSidebarWidth > 0) {
            sidebar.style.width = clampSidebarWidth(storedSidebarWidth) + 'px';
          }
        } catch (error) {
        }

        let resizingSidebar = false;
        let resizeStartX = 0;
        let resizeStartWidth = 0;

        const moveSidebarResize = function (event) {
          if (!resizingSidebar) {
            return;
          }

          sidebar.style.width = clampSidebarWidth(
            resizeStartWidth + event.clientX - resizeStartX
          ) + 'px';
        };

        const finishSidebarResize = function () {
          if (!resizingSidebar) {
            return;
          }

          resizingSidebar = false;
          sidebarResizer.classList.remove('is-resizing');
          document.body.classList.remove('map-app-resizing');
          window.removeEventListener('pointermove', moveSidebarResize);
          window.removeEventListener('pointerup', finishSidebarResize);
          saveSidebarWidth();
        };

        sidebarResizer.addEventListener('pointerdown', function (event) {
          if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
          }

          resizingSidebar = true;
          resizeStartX = event.clientX;
          resizeStartWidth = sidebar.getBoundingClientRect().width;
          sidebarResizer.classList.add('is-resizing');
          document.body.classList.add('map-app-resizing');
          event.preventDefault();
        });

        window.addEventListener('pointermove', moveSidebarResize);
        window.addEventListener('pointerup', finishSidebarResize);

        window.addEventListener('resize', function () {
          if (sidebar.style.width) {
            sidebar.style.width = clampSidebarWidth(
              sidebar.getBoundingClientRect().width
            ) + 'px';
          }
        });

        sidebarResizer.addEventListener('keydown', function (event) {
          if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
          }

          const delta = event.key === 'ArrowRight' ? 20 : -20;
          sidebar.style.width = clampSidebarWidth(
            sidebar.getBoundingClientRect().width + delta
          ) + 'px';
          saveSidebarWidth();
          event.preventDefault();
        });
      }

      const municipalities = @json($mapMunicipalities);
      const schoolDistrictUrlTemplate = @json($schoolDistrictUrlTemplate);
      const historicalUrlTemplate = @json($historicalUrlTemplate);
      const populationUrl = @json(route('disasters.population', ['disaster' => $disaster->id]));
      const smallAreaUrl = @json(route('disasters.small_areas', ['disaster' => $disaster->id]));
      const evacuationShelterUrl = @json(route('disasters.evacuation_shelters', ['disaster' => $disaster->id]));
      const volunteerCenterUrl = @json(route('disasters.volunteer_centers', ['disaster' => $disaster->id]));
      const roadRegulationUrl = @json(url('/api/v1/disaster/roads'));
      const wasteSiteUrl = @json(url('/api/v1/disaster/waste-sites'));
      const meetingSummaryUrl = @json(url('/api/v1/disaster/meetings'));
      const mapElement = document.getElementById('disaster-map');

      if (!mapElement) {
        return;
      }

      const map = L.map(mapElement, {
        scrollWheelZoom: true,
        zoomControl: false,
      });

      L.control.zoom({ position: 'topright' }).addTo(map);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      map.attributionControl.addAttribution(
        '行政区域: <a href="https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-N03-v3_1.html" target="_blank" rel="noopener">国土数値情報（国土交通省）</a>'
      );
      map.attributionControl.addAttribution(
        '学区: <a href="https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-A27-2023.html" target="_blank" rel="noopener">国土数値情報 A27/A32</a>'
      );
      map.attributionControl.addAttribution(
        '合併前行政界: <a href="https://geoshape.ex.nii.ac.jp/city/" target="_blank" rel="noopener">歴史的行政区域データセットβ版（CODH）</a>'
      );
      map.attributionControl.addAttribution(
        '小区域: <a href="https://www.e-stat.go.jp/gis/statmap-search?page=1&type=2&aggregateUnitForBoundary=A&toukeiCode=00200521" target="_blank" rel="noopener">令和2年国勢調査 町丁・字等境界データ</a>'
      );
      map.attributionControl.addAttribution(
        '避難所: <a href="https://hinanmap.gsi.go.jp/" target="_blank" rel="noopener">国土地理院 指定緊急避難場所・指定避難所データ</a>'
      );
      map.attributionControl.addAttribution(
        'ボランティア: <a href="https://www.saigaivc.com/" target="_blank" rel="noopener">全国社会福祉協議会 被災者支援・ボランティア情報</a>'
      );
      map.attributionControl.addAttribution(
        '道路規制・仮置場: 千葉県防災ポータル、自治体公開情報等'
      );

      const layers = {
        municipalities: L.featureGroup().addTo(map),
        small_areas: L.featureGroup(),
        shelters: L.featureGroup(),
        volunteer_centers: L.featureGroup(),
        roads: L.featureGroup(),
        waste_sites: L.featureGroup(),
        elementary: L.featureGroup(),
        junior_high: L.featureGroup(),
        historical: L.featureGroup(),
      };

      const layerStyles = {
        municipalities: {
          color: '#1f3c88',
          weight: 2,
          fillColor: '#1f3c88',
          fillOpacity: 0.08
        },
        small_areas: {
          color: '#6c757d',
          weight: 1,
          opacity: 0.85,
          fillColor: '#adb5bd',
          fillOpacity: 0.02
        },
        elementary: {
          color: '#fd7e14',
          weight: 1.5,
          fillColor: '#fd7e14',
          fillOpacity: 0.18
        },
        junior_high: {
          color: '#6f42c1',
          weight: 1.5,
          fillColor: '#6f42c1',
          fillOpacity: 0.18
        },
        historical: {
          color: '#495057',
          weight: 1,
          fill: true,
          fillColor: '#adb5bd',
          fillOpacity: 0.08,
          dashArray: '4,2'
        }
      };

      const layerLoads = {};
      let shelterMarkers = [];
      let shelterLayerLoaded = false;
      let historicalFeatures = [];
      let historicalRequestId = 0;
      let selectedBoundaryLayer = null;
      const layerStorageKey = 'disaster-map-layer-visibility';
      const shelterActiveOnlyStorageKey = 'disaster-map-shelter-active-only';
      let mapViewInitialized = false;

      function loadLayerVisibility() {
        try {
          const stored = window.localStorage.getItem(layerStorageKey);

          if (!stored) {
            return {};
          }

          const parsed = JSON.parse(stored);

          return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
          return {};
        }
      }

      function saveLayerVisibility() {
        const visibility = {};

        document.querySelectorAll('.map-layer-toggle').forEach(function (toggle) {
          visibility[toggle.dataset.layer] = toggle.checked;
        });

        try {
          window.localStorage.setItem(layerStorageKey, JSON.stringify(visibility));
        } catch (error) {
        }
      }

      function setLayerStatus(message) {
        const status = document.getElementById('layer-status');
        if (status) {
          status.textContent = message || '';
        }
      }

      function resetHistoricalNameOptions(message) {
        historicalFeatures = [];
        clearBoundaryHighlight();

        const select = document.getElementById('historical-name');

        if (!select) {
          return;
        }

        select.innerHTML = '';

        const option = document.createElement('option');
        option.value = '';
        option.textContent = message;
        select.appendChild(option);
        select.disabled = true;
      }

      function populateHistoricalNameOptions() {
        const select = document.getElementById('historical-name');

        if (!select) {
          return;
        }

        select.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '旧市町村を選択してください';
        select.appendChild(placeholder);

        const names = new Set();

        historicalFeatures.forEach(function (item, index) {
          if (!item.name || names.has(item.name)) {
            return;
          }

          names.add(item.name);

          const option = document.createElement('option');
          option.value = String(index);
          option.textContent = item.name;
          select.appendChild(option);
        });

        select.disabled = historicalFeatures.length === 0;
      }

      function fitMapToVisibleLayers() {
        const bounds = L.latLngBounds([]);

        Object.keys(layers).forEach(function (name) {
          if (map.hasLayer(layers[name]) && layers[name].getLayers().length > 0) {
            bounds.extend(layers[name].getBounds());
          }
        });

        if (bounds.isValid()) {
          map.fitBounds(bounds, {
            padding: [20, 20],
            maxZoom: 12
          });
          mapViewInitialized = true;
        }
      }

      function ensureMapView() {
        if (!mapViewInitialized) {
          map.setView([36.2048, 138.2529], 5);
          mapViewInitialized = true;
        }
      }

      function layerStyle(name) {
        return layerStyles[name] || layerStyles.municipalities;
      }

      function boundaryBaseStyle(layer) {
        return {
          color: layer.options.color,
          weight: layer.options.weight,
          opacity: layer.options.opacity,
          fill: layer.options.fill,
          fillColor: layer.options.fillColor,
          fillOpacity: layer.options.fillOpacity,
          dashArray: layer.options.dashArray
        };
      }

      function clearBoundaryHighlight() {
        if (selectedBoundaryLayer && selectedBoundaryLayer.setStyle) {
          selectedBoundaryLayer.setStyle(
            selectedBoundaryLayer.saizBaseStyle || layerStyle('historical')
          );
        }

        selectedBoundaryLayer = null;
      }

      function highlightBoundary(layer) {
        if (!layer || !layer.setStyle) {
          return;
        }

        if (selectedBoundaryLayer !== layer) {
          clearBoundaryHighlight();
        }

        if (!layer.saizBaseStyle) {
          layer.saizBaseStyle = boundaryBaseStyle(layer);
        }

        layer.setStyle({
          color: '#dc3545',
          weight: 4,
          opacity: 1,
          fill: false,
          fillOpacity: 0,
          dashArray: null
        });

        if (layer.bringToFront) {
          layer.bringToFront();
        }

        selectedBoundaryLayer = layer;
      }

      let populationLoaded = false;

      function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function formatPopulationNumber(value) {
        return Number(value || 0).toLocaleString('ja-JP');
      }

      function populationPercent(part, total) {
        if (!total) {
          return '0.0';
        }

        return ((Number(part || 0) / Number(total)) * 100).toFixed(1);
      }

      function populationSourceLink(source) {
        if (!source) {
          return '';
        }

        const name = escapeHtml(source.name || '出典');
        const url = escapeHtml(source.url || '');

        if (!url) {
          return name;
        }

        return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + name + '</a>';
      }

      function renderPopulationMunicipality(data) {
        const municipality = data.municipality || {};
        const summary = data.summary || {};
        const foreigners = summary.foreigners || {};
        const ageGroups = data.age_groups || {};
        const groups = ageGroups.groups || [];
        const smallAreas = (data.small_areas && data.small_areas.items) || [];
        const population = Number(summary.population || 0);
        const foreignerTotal = Number(foreigners.total || 0);
        const age65 = groups.reduce(function (total, group) {
          return total + (parseInt(group.label, 10) >= 65 ? Number(group.total || 0) : 0);
        }, 0);
        const maxAgeTotal = Math.max.apply(null, groups.map(function (group) {
          return Number(group.total || 0);
        }).concat([1]));

        const ageRows = groups.map(function (group) {
          const total = Number(group.total || 0);
          const foreignerCount = Number(group.foreigners || 0);
          const width = Math.max(0, Math.min(100, (total / maxAgeTotal) * 100));
          const foreignerRow = foreignerCount > 0
            ? '<span class="population-age-foreigners">外国人 ' + formatPopulationNumber(foreignerCount) + '人</span>'
            : '';

          return '<div class="population-age-row">'
            + '<span class="population-age-label">' + escapeHtml(group.label) + '</span>'
            + '<div class="population-age-bar"><span style="width: ' + width.toFixed(1) + '%"></span></div>'
            + '<span class="population-age-total">' + formatPopulationNumber(total) + '</span>'
            + foreignerRow
            + '</div>';
        }).join('');

        const smallAreaRows = smallAreas.map(function (area) {
          return '<tr>'
            + '<td>' + escapeHtml(area.name) + '</td>'
            + '<td class="number">' + formatPopulationNumber(area.population) + '</td>'
            + '<td class="number">' + formatPopulationNumber(area.age_65_plus) + '</td>'
            + '<td class="number">' + populationPercent(area.age_65_plus, area.population) + '%</td>'
            + '</tr>';
        }).join('');

        return '<details class="population-municipality" open>'
          + '<summary class="population-header">'
          + '<span class="population-title">' + escapeHtml(municipality.name) + '</span>'
          + '<span class="population-date">' + escapeHtml(summary.as_of || '') + '現在</span>'
          + '</summary>'
          + '<div class="population-stats">'
          + '<div class="population-stat"><span class="population-stat-label">総人口</span><span class="population-stat-value">' + formatPopulationNumber(population) + '</span></div>'
          + '<div class="population-stat"><span class="population-stat-label">世帯数</span><span class="population-stat-value">' + formatPopulationNumber(summary.households) + '</span></div>'
          + '<div class="population-stat"><span class="population-stat-label">外国人</span><span class="population-stat-value">' + formatPopulationNumber(foreignerTotal) + '</span></div>'
          + '<div class="population-stat"><span class="population-stat-label">外国人比率</span><span class="population-stat-value">' + populationPercent(foreignerTotal, population) + '%</span></div>'
          + '<div class="population-stat"><span class="population-stat-label">65歳以上（2020年）</span><span class="population-stat-value">' + formatPopulationNumber(age65) + '</span></div>'
          + '<div class="population-stat"><span class="population-stat-label">高齢化率（2020年）</span><span class="population-stat-value">' + populationPercent(age65, groups.reduce(function (total, group) { return total + Number(group.total || 0); }, 0)) + '%</span></div>'
          + '</div>'
          + '<p class="population-source">出典: ' + populationSourceLink(summary.source) + '</p>'
          + '<h3 class="population-section-title">年齢階層 <small>' + escapeHtml(ageGroups.as_of || '') + ' / 国勢調査</small></h3>'
          + '<div class="population-age-list">' + ageRows + '</div>'
          + '<p class="population-source">出典: ' + populationSourceLink(ageGroups.source) + '</p>'
          + '<details class="population-small-areas">'
          + '<summary>小区域（町丁・字等） ' + smallAreas.length + '件</summary>'
          + '<div class="population-table-wrap">'
          + '<table class="population-table"><thead><tr><th>地区</th><th>人口</th><th>65歳以上</th><th>高齢化率</th></tr></thead>'
          + '<tbody>' + smallAreaRows + '</tbody></table>'
          + '</div>'
          + '<p class="population-source">出典: ' + populationSourceLink(data.small_areas && data.small_areas.source) + '</p>'
          + '</details>'
          + '<p class="population-mesh-note">メッシュデータは未取込です。</p>'
          + '</details>';
      }

      function renderPopulation(data) {
        const container = document.getElementById('population-content');

        if (!container) {
          return;
        }

        const municipalitiesData = data.municipalities || [];

        if (municipalitiesData.length === 0) {
          const missing = (data.meta && data.meta.missing) || [];
          container.innerHTML = '<p class="population-empty mb-0">人口データが登録されていません。'
            + (missing.length > 0 ? '<br>未取込: ' + escapeHtml(missing.join('、')) : '')
            + '</p>';
          return;
        }

        container.innerHTML = municipalitiesData.map(renderPopulationMunicipality).join('');
      }

      function loadPopulationData() {
        if (populationLoaded) {
          return;
        }

        populationLoaded = true;

        fetch(populationUrl, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(renderPopulation)
          .catch(function () {
            populationLoaded = false;

            const container = document.getElementById('population-content');

            if (container) {
              container.innerHTML = '<p class="population-empty mb-0">人口データを取得できませんでした。</p>';
            }
          });
      }

      function loadMunicipalitiesLayer() {
        const requests = [];

        municipalities.forEach(function (municipality) {
          municipality.codes.forEach(function (code) {
            const url = 'https://geolonia.github.io/japanese-admins/'
              + municipality.prefecture_code + '/' + code + '.json';

            requests.push(
              fetch(url)
                .then(function (response) {
                  if (!response.ok) {
                    throw new Error(response.status);
                  }
                  return response.json();
                })
                .then(function (geojson) {
                  L.geoJSON(geojson, {
                    style: layerStyle('municipalities'),
                    onEachFeature: function (feature, layer) {
                      layer.on('click', function () {
                        highlightBoundary(layer);
                      });
                    }
                  }).addTo(layers.municipalities);
                })
                .catch(function () {
                  // 取得できない区域はスキップする
                })
            );
          });
        });

        return Promise.all(requests).then(function () {
          if (layers.municipalities.getLayers().length > 0) {
            fitMapToVisibleLayers();
          } else {
            const status = document.getElementById('disaster-map-status');
            if (status) {
              status.textContent = '行政区域データを取得できませんでした。時間をおいて再度お試しください。';
            }
          }

          ensureMapView();

          window.setTimeout(function () {
            map.invalidateSize();
          }, 100);
        });
      }

      function navigationUrl(latitude, longitude) {
        return 'https://www.google.com/maps/dir/?api=1&destination='
          + encodeURIComponent(latitude + ',' + longitude)
          + '&travelmode=driving&dir_action=navigate';
      }

      function appendNavigationButton(popup, latitude, longitude) {
        const link = document.createElement('a');
        link.className = 'map-popup-navigation';
        link.href = navigationUrl(latitude, longitude);
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = 'ここに行く';
        link.setAttribute('aria-label', 'Googleマップでここへの経路を開く');
        popup.appendChild(link);
      }

      function shelterIcon(type, isActive) {
        let className = type === 'emergency_site'
          ? 'shelter-marker shelter-marker-emergency'
          : 'shelter-marker';

        if (isActive) {
          className = 'shelter-marker shelter-marker-active';
        }

        return L.divIcon({
          className: className,
          html: '<span></span>',
          iconSize: [18, 18],
          iconAnchor: [9, 9],
          popupAnchor: [0, -10]
        });
      }

      function shelterActiveOnlyEnabled() {
        const toggle = document.getElementById('shelter-active-only');
        return Boolean(toggle && toggle.checked);
      }

      function applyShelterFilter() {
        const activeOnly = shelterActiveOnlyEnabled();
        const total = shelterMarkers.length;
        const activeTotal = shelterMarkers.filter(function (entry) {
          return entry.active;
        }).length;

        shelterMarkers.forEach(function (entry) {
          if (!activeOnly || entry.active) {
            if (!layers.shelters.hasLayer(entry.marker)) {
              entry.marker.addTo(layers.shelters);
            }
          } else if (layers.shelters.hasLayer(entry.marker)) {
            layers.shelters.removeLayer(entry.marker);
          }
        });

        const visible = layers.shelters.getLayers().length;

        if (activeOnly) {
          setLayerStatus(visible > 0
            ? '開設中の避難所を表示中（' + visible + '件 / 全' + total + '件）'
            : '開設中の避難所はありません（全' + total + '件）');
        } else {
          setLayerStatus('避難所を表示中（' + total + '件 / 開設中 ' + activeTotal + '件）');
        }

        if (map.hasLayer(layers.shelters) && visible > 0) {
          fitMapToVisibleLayers();
        }
      }

      function loadShelterLayer() {
        if (shelterLayerLoaded) {
          return Promise.resolve();
        }

        if (layerLoads.shelters) {
          return layerLoads.shelters;
        }

        const toggle = document.querySelector('.map-layer-toggle[data-layer="shelters"]');

        layerLoads.shelters = fetch(evacuationShelterUrl, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            const shelters = data.shelters || [];

            if (shelters.length === 0) {
              shelterLayerLoaded = true;
              setLayerStatus('避難所データは未登録です。');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers.shelters);
              return;
            }

            shelterMarkers = [];

            shelters.forEach(function (shelter) {
              const latitude = Number(shelter.latitude);
              const longitude = Number(shelter.longitude);

              if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return;
              }

              const marker = L.marker([latitude, longitude], {
                icon: shelterIcon(shelter.shelter_type, shelter.is_active),
                zIndexOffset: shelter.is_active ? 1000 : 0
              });
              const popup = document.createElement('div');
              const title = document.createElement('strong');
              title.textContent = shelter.name;
              popup.appendChild(title);

              if (shelter.is_active) {
                const active = document.createElement('div');
                active.style.color = '#dc3545';
                active.style.fontWeight = 'bold';
                active.textContent = '開設中';
                popup.appendChild(active);
              }

              const type = document.createElement('div');
              type.textContent = shelter.shelter_type === 'emergency_site'
                ? '指定緊急避難場所'
                : '指定避難所';
              popup.appendChild(type);

              if (shelter.address) {
                const address = document.createElement('div');
                address.textContent = shelter.address;
                popup.appendChild(address);
              }

              appendNavigationButton(popup, latitude, longitude);

              if (Array.isArray(shelter.disaster_types) && shelter.disaster_types.length > 0) {
                const disasters = document.createElement('div');
                disasters.textContent = '対象災害: ' + shelter.disaster_types.join('、');
                popup.appendChild(disasters);
              }

              if (shelter.note) {
                const note = document.createElement('div');
                note.textContent = shelter.note;
                popup.appendChild(note);
              }

              if (shelter.is_active && shelter.evacuees !== null && shelter.evacuees !== undefined) {
                const evacuees = document.createElement('div');
                evacuees.textContent = '避難者数: ' + Number(shelter.evacuees).toLocaleString('ja-JP') + '人';
                popup.appendChild(evacuees);
              }

              if (shelter.is_active && shelter.status_note) {
                const statusNote = document.createElement('div');
                statusNote.textContent = shelter.status_note;
                popup.appendChild(statusNote);
              }

              if (shelter.status_checked_at) {
                const checked = document.createElement('div');
                checked.style.color = '#6c757d';
                checked.textContent = '開設状況確認: ' + new Date(shelter.status_checked_at).toLocaleString('ja-JP');
                popup.appendChild(checked);
              }

              marker.bindPopup(popup);
              shelterMarkers.push({
                marker: marker,
                active: Boolean(shelter.is_active)
              });
            });

            shelterLayerLoaded = true;
            applyShelterFilter();
          })
          .catch(function () {
            shelterLayerLoaded = false;
            shelterMarkers = [];
            setLayerStatus('避難所データを取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.shelters);
          })
          .finally(function () {
            layerLoads.shelters = null;
          });

        return layerLoads.shelters;
      }

      function volunteerCenterIcon() {
        return L.divIcon({
          className: 'volunteer-center-marker',
          html: '<span></span>',
          iconSize: [20, 20],
          iconAnchor: [10, 10],
          popupAnchor: [0, -12]
        });
      }

      function loadVolunteerCenterLayer() {
        if (layers.volunteer_centers.getLayers().length > 0) {
          return Promise.resolve();
        }

        if (layerLoads.volunteer_centers) {
          return layerLoads.volunteer_centers;
        }

        const toggle = document.querySelector('.map-layer-toggle[data-layer="volunteer_centers"]');

        layerLoads.volunteer_centers = fetch(volunteerCenterUrl, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            const centers = data.centers || [];

            if (centers.length === 0) {
              setLayerStatus('ボランティアセンターは未登録です。');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers.volunteer_centers);
              return;
            }

            centers.forEach(function (center) {
              const latitude = Number(center.latitude);
              const longitude = Number(center.longitude);

              if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return;
              }

              const marker = L.marker([latitude, longitude], {
                icon: volunteerCenterIcon()
              });
              const popup = document.createElement('div');
              const title = document.createElement('strong');
              title.textContent = center.name;
              popup.appendChild(title);

              const type = document.createElement('div');
              type.textContent = '災害ボランティアセンター';
              popup.appendChild(type);

              if (center.address) {
                const address = document.createElement('div');
                address.textContent = center.address;
                popup.appendChild(address);
              }

              if (center.phone) {
                const phone = document.createElement('div');
                phone.textContent = 'TEL: ' + center.phone;
                popup.appendChild(phone);
              }

              appendNavigationButton(popup, latitude, longitude);

              const recruitment = center.latest_recruitment || {};
              const recruitmentBox = document.createElement('div');
              recruitmentBox.style.marginTop = '0.5rem';

              const recruitmentLabel = document.createElement('strong');
              recruitmentLabel.textContent = '最新のボランティア募集';
              recruitmentBox.appendChild(recruitmentLabel);

              if (recruitment.title) {
                const recruitmentTitle = document.createElement('div');
                recruitmentTitle.textContent = recruitment.title;
                recruitmentBox.appendChild(recruitmentTitle);
              }

              if (recruitment.summary) {
                const recruitmentSummary = document.createElement('div');
                recruitmentSummary.textContent = recruitment.summary;
                recruitmentBox.appendChild(recruitmentSummary);
              }

              if (recruitment.url) {
                const recruitmentLink = document.createElement('a');
                recruitmentLink.href = recruitment.url;
                recruitmentLink.target = '_blank';
                recruitmentLink.rel = 'noopener noreferrer';
                recruitmentLink.textContent = '募集情報の詳細';
                recruitmentBox.appendChild(recruitmentLink);
              }

              if (recruitment.updated_at) {
                const updated = document.createElement('div');
                updated.style.color = '#6c757d';
                updated.textContent = '情報更新: ' + new Date(recruitment.updated_at).toLocaleString('ja-JP');
                recruitmentBox.appendChild(updated);
              }

              if (!recruitment.title && !recruitment.summary) {
                const empty = document.createElement('div');
                empty.textContent = '募集情報は未取得です。';
                recruitmentBox.appendChild(empty);
              }

              popup.appendChild(recruitmentBox);

              if (center.url) {
                const site = document.createElement('a');
                site.href = center.url;
                site.target = '_blank';
                site.rel = 'noopener noreferrer';
                site.textContent = '公式サイト';
                site.style.display = 'block';
                site.style.marginTop = '0.35rem';
                popup.appendChild(site);
              }

              marker.bindPopup(popup);
              marker.addTo(layers.volunteer_centers);
            });

            setLayerStatus('ボランティアセンターを表示中（' + layers.volunteer_centers.getLayers().length + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function () {
            setLayerStatus('ボランティアセンター情報を取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.volunteer_centers);
          })
          .finally(function () {
            layerLoads.volunteer_centers = null;
          });

        return layerLoads.volunteer_centers;
      }

      const supportRoadFeatures = [];
      const supportWasteFeatures = [];
      const supportMeetingItems = [];
      const supportRoadLayers = new Map();
      const supportWasteLayers = new Map();

      function roadTypeMeta(type) {
        return {
          full_closure: { label: '全面通行止', color: '#dc3545', guidance: 'この区間は通行できません。迂回路を確認してください。' },
          alternating: { label: '片側交互通行', color: '#fd7e14', guidance: '片側交互で通行できます。現地の誘導に従ってください。' },
          large_vehicle: { label: '大型車規制', color: '#6f42c1', guidance: '大型車は通行できません。車両制限を確認してください。' },
          weight_limit: { label: '重量規制', color: '#6f42c1', guidance: '重量制限があります。積載量を確認してください。' }
        }[type] || { label: 'その他規制', color: '#495057', guidance: '現地の規制表示を確認してください。' };
      }

      function vehicleLabels(vehicles) {
        const labels = {
          light_truck: '軽トラ',
          '2t': '2t車',
          '4t': '4t車',
          large: '大型回送車'
        };

        if (!Array.isArray(vehicles) || vehicles.length === 0) {
          return '車両制限なし・不明';
        }

        return vehicles.map(function (vehicle) {
          return labels[vehicle] || vehicle;
        }).join(' / ');
      }

      function sourceHtml(properties) {
        const source = properties.source || {};
        const url = String(source.url || '');

        return '<div class="support-item-meta">'
          + '出典: ' + escapeHtml(source.org || '不明')
          + (url.startsWith('http') ? '<br><a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">一次資料を開く</a>' : '')
          + (source.published_at ? '<br>公表: ' + escapeHtml(new Date(source.published_at).toLocaleString('ja-JP')) : '')
          + (source.credibility ? '<br>出典信頼度: ' + escapeHtml(source.credibility) : '')
          + (source.credibility_reason ? '<br>判定理由: ' + escapeHtml(source.credibility_reason) : '')
          + '</div>';
      }

      function roadPopup(properties) {
        const meta = roadTypeMeta(properties.regulation_type);
        const root = document.createElement('div');

        root.innerHTML = '<strong>' + escapeHtml(properties.road_name || '道路規制') + '</strong>'
          + '<div style="margin:0.3rem 0;"><span class="support-badge" style="background:' + meta.color + '">' + escapeHtml(meta.label) + '</span></div>'
          + '<div style="font-weight:600;margin-bottom:0.35rem;">' + escapeHtml(meta.guidance) + '</div>'
          + '<div><strong>区間:</strong> ' + escapeHtml(properties.section || '不明') + '</div>'
          + '<div><strong>対象車両:</strong> ' + escapeHtml(vehicleLabels(properties.vehicle_classes)) + '</div>'
          + '<div><strong>事由:</strong> ' + escapeHtml(properties.reason || '不明') + '</div>'
          + '<div class="support-item-meta">信頼度: ' + escapeHtml(properties.confidence_rank || 'C') + '</div>'
          + sourceHtml(properties)
          + '<div class="support-item-meta" style="color:#b02a37;margin-top:0.35rem;">' + escapeHtml(properties.disclaimer || '出典の一次情報を確認してください。') + '</div>';

        return root;
      }

      function wasteStatusMeta(status) {
        return {
          open: { label: '開設中', color: '#198754' },
          closed: { label: '閉鎖', color: '#6c757d' }
        }[status] || { label: '不明', color: '#0d6efd' };
      }

      function supportConditionsHtml(conditions) {
        const labels = {
          contact: '連絡先',
          other_contact: 'その他連絡先',
          fee_free: '料金',
          id_verification: '本人確認',
          permit_required: '許可申請',
          reception_hours: '受付時間',
          application_form: '申請書',
          permit_form: '申請書',
          sorting_requested: '分別',
          business_waste_accepted: '事業系ごみ',
          direct_dropoff_by_citizen: '市民直接搬入',
          notes: '注意事項',
          unloading: '搬入方法',
          closure_conditions: '閉鎖条件',
          required_documents: '必要書類',
          advance_reservation_required: '事前予約',
          advance_consultation_required_for_large_loads: '多量搬入の事前相談'
        };
        const booleans = {
          fee_free: ['無料', '有料'],
          id_verification: ['必要', '不要'],
          permit_required: ['必要', '不要'],
          sorting_requested: ['必要', '不要'],
          business_waste_accepted: ['可', '不可'],
          direct_dropoff_by_citizen: ['可', '不可'],
          advance_reservation_required: ['必要', '不要'],
          advance_consultation_required_for_large_loads: ['必要', '不要']
        };
        const entries = Object.entries(conditions || {}).filter(function (entry) {
          return entry[1] !== null && entry[1] !== undefined && entry[1] !== '';
        });

        if (!entries.length) {
          return '<div class="support-item-meta">不明</div>';
        }

        return entries.map(function (entry) {
          const key = entry[0];
          const value = entry[1];
          let text;

          if (typeof value === 'boolean') {
            const pair = booleans[key] || ['あり', 'なし'];
            text = value ? pair[0] : pair[1];
          } else if (Array.isArray(value)) {
            text = value.join('、');
          } else if (typeof value === 'object') {
            text = JSON.stringify(value);
          } else {
            text = String(value);
          }

          return '<div><strong>' + escapeHtml(labels[key] || key) + ':</strong> ' + escapeHtml(text) + '</div>';
        }).join('');
      }

      function wastePopup(properties) {
        const meta = wasteStatusMeta(properties.status);
        const root = document.createElement('div');

        root.innerHTML = '<strong>' + escapeHtml(properties.name || '仮置場') + '</strong>'
          + '<div style="margin:0.3rem 0;"><span class="support-badge" style="background:' + meta.color + '">' + escapeHtml(meta.label) + '</span></div>'
          + '<div><strong>受入品目:</strong> ' + escapeHtml((properties.accepted_items || []).join('、') || '不明') + '</div>'
          + '<div><strong>受入不可:</strong> ' + escapeHtml((properties.rejected_items || []).join('、') || '不明') + '</div>'
          + '<div><strong>車両制限:</strong> ' + escapeHtml(properties.vehicle_limit || (properties.max_payload_tons ? properties.max_payload_tons + 'tまで' : '不明')) + '</div>'
          + '<div><strong>搬入条件:</strong></div>' + supportConditionsHtml(properties.support_conditions || {})
          + sourceHtml(properties)
          + '<div class="support-item-meta" style="color:#b02a37;margin-top:0.35rem;">' + escapeHtml(properties.disclaimer || '出典の一次情報を確認してください。') + '</div>';

        return root;
      }

      function focusSupportFeature(layerName, id, layersById) {
        const layer = layersById.get(id);
        const toggle = document.querySelector('.map-layer-toggle[data-layer="' + layerName + '"]');

        if (toggle && !toggle.checked) {
          toggle.checked = true;
          map.addLayer(layers[layerName]);
          saveLayerVisibility();
        }

        if (!layer) {
          return;
        }

        if (layer.getBounds && layer.getBounds().isValid()) {
          map.fitBounds(layer.getBounds(), { padding: [40, 40], maxZoom: 15 });
        } else if (layer.getLatLng) {
          map.setView(layer.getLatLng(), 15);
        }

        if (layer.openPopup) {
          layer.openPopup();
        }
      }

      function renderSupportRoadList() {
        const list = document.getElementById('support-road-list');
        const count = document.getElementById('support-road-count');

        if (!list) {
          return;
        }

        if (count) {
          count.textContent = supportRoadFeatures.length + '件';
        }

        if (supportRoadFeatures.length === 0) {
          list.innerHTML = '<p class="support-empty">道路規制は登録されていません。</p>';
          return;
        }

        list.innerHTML = '';
        supportRoadFeatures.forEach(function (feature) {
          const properties = feature.properties || {};
          const meta = roadTypeMeta(properties.regulation_type);
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'support-item';
          button.innerHTML = '<span class="support-badge" style="background:' + meta.color + '">' + escapeHtml(meta.label) + '</span>'
            + '<span class="support-item-name">' + escapeHtml(properties.road_name || '道路名不明') + '</span>'
            + '<span class="support-item-meta">' + escapeHtml(properties.section || '区間不明') + '</span>'
            + '<span class="support-item-meta">対象: ' + escapeHtml(vehicleLabels(properties.vehicle_classes)) + '</span>';
          button.addEventListener('click', function () {
            focusSupportFeature('roads', properties.id, supportRoadLayers);
          });
          list.appendChild(button);
        });
      }

      function renderSupportWasteList() {
        const list = document.getElementById('support-waste-list');
        const count = document.getElementById('support-waste-count');

        if (!list) {
          return;
        }

        if (count) {
          count.textContent = supportWasteFeatures.length + '件';
        }

        if (supportWasteFeatures.length === 0) {
          list.innerHTML = '<p class="support-empty">仮置場は登録されていません。</p>';
          return;
        }

        list.innerHTML = '';
        supportWasteFeatures.forEach(function (feature) {
          const properties = feature.properties || {};
          const meta = wasteStatusMeta(properties.status);
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'support-item';
          button.innerHTML = '<span class="support-badge" style="background:' + meta.color + '">' + escapeHtml(meta.label) + '</span>'
            + '<span class="support-item-name">' + escapeHtml(properties.name || '仮置場名不明') + '</span>'
            + '<span class="support-item-meta">受入: ' + escapeHtml((properties.accepted_items || []).join('、') || '不明') + '</span>'
            + '<span class="support-item-meta">車両: ' + escapeHtml(properties.vehicle_limit || (properties.max_payload_tons ? properties.max_payload_tons + 'tまで' : '不明')) + '</span>';
          button.addEventListener('click', function () {
            focusSupportFeature('waste_sites', properties.id, supportWasteLayers);
          });
          list.appendChild(button);
        });
      }

      function meetingItemsHtml(items) {
        if (!Array.isArray(items) || items.length === 0) {
          return '';
        }

        return '<ul>' + items.map(function (item) {
          return '<li>' + escapeHtml(String(item)) + '</li>';
        }).join('') + '</ul>';
      }

      function meetingBlock(label, items) {
        const html = meetingItemsHtml(items);

        if (!html) {
          return '';
        }

        return '<div class="support-meeting-block"><strong>' + escapeHtml(label) + '</strong>' + html + '</div>';
      }

      function renderSupportMeetingList() {
        const list = document.getElementById('support-meeting-list');
        const count = document.getElementById('support-meeting-count');

        if (!list) {
          return;
        }

        if (count) {
          count.textContent = supportMeetingItems.length + '件';
        }

        if (supportMeetingItems.length === 0) {
          list.innerHTML = '<p class="support-empty">議事録は登録されていません。</p>';
          return;
        }

        list.innerHTML = supportMeetingItems.map(function (meeting) {
          const source = meeting.source || {};
          const heldAt = meeting.held_at ? new Date(meeting.held_at).toLocaleString('ja-JP') : '日時不明';
          const blocks = meetingBlock('決定事項', meeting.decisions)
            + meetingBlock('方針転換・撤収予定', meeting.policy_changes)
            + meetingBlock('未充足ニーズ・支援の空白', meeting.unmet_needs);

          return '<article class="support-meeting">'
            + '<span class="support-meeting-title">' + escapeHtml(meeting.meeting_name || '会議名不明') + '</span>'
            + '<span class="support-item-meta">' + escapeHtml(heldAt) + (source.org ? ' / ' + escapeHtml(source.org) : '') + '</span>'
            + (blocks || '<div class="support-item-meta">抽出された決定事項・ニーズはありません。</div>')
            + '<div class="support-meeting-source">出典: ' + escapeHtml(source.org || '不明')
            + (source.url ? '<br><a href="' + escapeHtml(source.url) + '" target="_blank" rel="noopener noreferrer">一次資料を開く</a>' : '')
            + (source.credibility ? '<br>出典信頼度: ' + escapeHtml(source.credibility) : '')
            + (source.credibility_reason ? '<br>判定理由: ' + escapeHtml(source.credibility_reason) : '')
            + '</div>'
            + '</article>';
        }).join('');
      }

      function updateSupportTabCount() {
        const badge = document.getElementById('support-tab-count');

        if (!badge) {
          return;
        }

        const total = supportRoadFeatures.length + supportWasteFeatures.length + supportMeetingItems.length;
        badge.textContent = String(total);
        badge.hidden = total === 0;
      }

      function loadRoadLayer() {
        if (supportRoadFeatures.length > 0) {
          return Promise.resolve();
        }

        if (layerLoads.roads) {
          return layerLoads.roads;
        }

        const toggle = document.querySelector('.map-layer-toggle[data-layer="roads"]');

        layerLoads.roads = fetch(roadRegulationUrl + '?disaster_id=' + encodeURIComponent(@json($disaster->id)), {
          headers: { 'Accept': 'application/json' }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            supportRoadFeatures.length = 0;
            supportRoadLayers.clear();

            (data.features || []).forEach(function (feature) {
              supportRoadFeatures.push(feature);
            });

            const geoLayer = L.geoJSON(data, {
              style: function (feature) {
                const meta = roadTypeMeta(feature.properties.regulation_type);
                return { color: meta.color, weight: 5, opacity: 0.95, lineCap: 'round' };
              },
              pointToLayer: function (feature, latlng) {
                const meta = roadTypeMeta(feature.properties.regulation_type);
                return L.circleMarker(latlng, {
                  radius: 8,
                  color: '#fff',
                  weight: 2,
                  fillColor: meta.color,
                  fillOpacity: 1
                });
              },
              onEachFeature: function (feature, layer) {
                const id = feature.properties.id;
                supportRoadLayers.set(id, layer);
                layer.bindPopup(roadPopup(feature.properties));
              }
            });

            layers.roads.clearLayers();
            geoLayer.addTo(layers.roads);
            renderSupportRoadList();
            updateSupportTabCount();
            setLayerStatus('道路規制を表示中（' + supportRoadFeatures.length + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function () {
            supportRoadFeatures.length = 0;
            supportRoadLayers.clear();
            renderSupportRoadList();
            updateSupportTabCount();
            setLayerStatus('道路規制データを取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.roads);
          })
          .finally(function () {
            layerLoads.roads = null;
          });

        return layerLoads.roads;
      }

      function loadWasteSiteLayer() {
        if (supportWasteFeatures.length > 0) {
          return Promise.resolve();
        }

        if (layerLoads.waste_sites) {
          return layerLoads.waste_sites;
        }

        const toggle = document.querySelector('.map-layer-toggle[data-layer="waste_sites"]');

        layerLoads.waste_sites = fetch(wasteSiteUrl + '?disaster_id=' + encodeURIComponent(@json($disaster->id)), {
          headers: { 'Accept': 'application/json' }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            supportWasteFeatures.length = 0;
            supportWasteLayers.clear();

            (data.features || []).forEach(function (feature) {
              supportWasteFeatures.push(feature);
            });

            const geoLayer = L.geoJSON(data, {
              pointToLayer: function (feature, latlng) {
                const meta = wasteStatusMeta(feature.properties.status);
                return L.circleMarker(latlng, {
                  radius: 9,
                  color: '#fff',
                  weight: 2,
                  fillColor: meta.color,
                  fillOpacity: 1
                });
              },
              onEachFeature: function (feature, layer) {
                const id = feature.properties.id;
                supportWasteLayers.set(id, layer);
                layer.bindPopup(wastePopup(feature.properties));
              }
            });

            layers.waste_sites.clearLayers();
            geoLayer.addTo(layers.waste_sites);
            renderSupportWasteList();
            updateSupportTabCount();
            setLayerStatus('仮置場を表示中（' + supportWasteFeatures.length + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function () {
            supportWasteFeatures.length = 0;
            supportWasteLayers.clear();
            renderSupportWasteList();
            updateSupportTabCount();
            setLayerStatus('仮置場データを取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.waste_sites);
          })
          .finally(function () {
            layerLoads.waste_sites = null;
          });

        return layerLoads.waste_sites;
      }

      function loadMeetingSummaries() {
        if (supportMeetingItems.length > 0) {
          return Promise.resolve();
        }

        if (layerLoads.meetings) {
          return layerLoads.meetings;
        }

        const list = document.getElementById('support-meeting-list');

        layerLoads.meetings = fetch(meetingSummaryUrl + '?disaster_id=' + encodeURIComponent(@json($disaster->id)), {
          headers: { 'Accept': 'application/json' }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            supportMeetingItems.length = 0;
            (data.meetings || []).forEach(function (meeting) {
              supportMeetingItems.push(meeting);
            });
            renderSupportMeetingList();
            updateSupportTabCount();
          })
          .catch(function () {
            if (list) {
              list.innerHTML = '<p class="support-empty">議事録を取得できませんでした。</p>';
            }
            const count = document.getElementById('support-meeting-count');
            if (count) {
              count.textContent = '取得失敗';
            }
          })
          .finally(function () {
            layerLoads.meetings = null;
          });

        return layerLoads.meetings;
      }

      function loadSupportPanelData() {
        loadRoadLayer();
        loadWasteSiteLayer();
        loadMeetingSummaries();
      }

      function loadSmallAreaLayer() {
        if (layers.small_areas.getLayers().length > 0) {
          return Promise.resolve();
        }

        if (layerLoads.small_areas) {
          return layerLoads.small_areas;
        }

        const toggle = document.querySelector('.map-layer-toggle[data-layer="small_areas"]');

        layerLoads.small_areas = fetch(smallAreaUrl, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            if (!data.features || data.features.length === 0) {
              setLayerStatus('小区域界のデータは未取込です。');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers.small_areas);
              return;
            }

            L.geoJSON(data, {
              style: layerStyle('small_areas'),
              onEachFeature: function (feature, layer) {
                const properties = feature.properties || {};
                const name = properties.name || '';
                const population = Number(properties.population || 0).toLocaleString('ja-JP');
                const households = Number(properties.households || 0).toLocaleString('ja-JP');

                if (name) {
                  const popup = document.createElement('div');
                  const title = document.createElement('strong');
                  title.textContent = name;
                  popup.appendChild(title);

                  const detail = document.createElement('div');
                  detail.textContent = '人口 ' + population + '人 / 世帯 ' + households;
                  popup.appendChild(detail);

                  layer.bindPopup(popup);
                }

                layer.on('click', function () {
                  highlightBoundary(layer);
                });
              }
            }).addTo(layers.small_areas);

            setLayerStatus('小区域界を表示中（' + data.features.length + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function () {
            setLayerStatus('小区域界のデータを取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.small_areas);
          })
          .finally(function () {
            layerLoads.small_areas = null;
          });

        return layerLoads.small_areas;
      }

      function loadSchoolDistrictLayer(name) {
        if (layers[name].getLayers().length > 0) {
          return Promise.resolve();
        }

        if (layerLoads[name]) {
          return layerLoads[name];
        }

        const label = name === 'elementary' ? '小学校区' : '中学校区';
        const url = schoolDistrictUrlTemplate.replace('TYPE', name);
        const toggle = document.querySelector('.map-layer-toggle[data-layer="' + name + '"]');

        layerLoads[name] = fetch(url, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            if (!data.features || data.features.length === 0) {
              setLayerStatus(label + 'のデータは未取込です。');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers[name]);
              return;
            }

            L.geoJSON(data, {
              style: layerStyle(name),
              onEachFeature: function (feature, layer) {
                const nameKey = name === 'elementary' ? 'A27_004' : 'A32_004';
                const providerKey = name === 'elementary' ? 'A27_002' : 'A32_002';
                const schoolName = feature.properties[nameKey] || '';
                const provider = feature.properties[providerKey] || '';

                if (schoolName) {
                  layer.bindPopup(document.createTextNode(
                    schoolName + (provider ? '（' + provider + '）' : '')
                  ));
                }

                layer.on('click', function () {
                  highlightBoundary(layer);
                });
              }
            }).addTo(layers[name]);

            setLayerStatus(label + 'を表示中（' + data.features.length + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function () {
            setLayerStatus(label + 'のデータを取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers[name]);
          })
          .finally(function () {
            layerLoads[name] = null;
          });

        return layerLoads[name];
      }

      function loadHistoricalLayer() {
        const dateElement = document.getElementById('historical-date');
        const toggle = document.querySelector('.map-layer-toggle[data-layer="historical"]');

        if (!dateElement || !toggle) {
          return Promise.resolve();
        }

        const date = dateElement.value;
        const url = historicalUrlTemplate.replace('DATE', date);
        const requestId = ++historicalRequestId;

        layers.historical.clearLayers();
        resetHistoricalNameOptions('読み込み中...');
        setLayerStatus('合併前行政界を読み込み中...');

        return fetch(url, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            if (requestId !== historicalRequestId) {
              return;
            }

            if (!data.layers || data.layers.length === 0) {
              setLayerStatus('合併前行政界のデータは未取込です。');
              resetHistoricalNameOptions('データがありません');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers.historical);
              return;
            }

            let featureCount = 0;

            data.layers.forEach(function (layer) {
              const topology = layer.topojson;

              if (!topology || !topology.objects || !topology.objects.city) {
                return;
              }

              const geojson = topojson.feature(topology, topology.objects.city);
              featureCount += (geojson.features || []).length;

              L.geoJSON(geojson, {
                style: layerStyle('historical'),
                onEachFeature: function (feature, leafletLayer) {
                  const properties = feature.properties || {};
                  const areaName = [properties.N03_003, properties.N03_004]
                    .filter(function (value) { return value; })
                    .join('');
                  const from = properties.N03_005 || '';
                  const to = properties.N03_006 || '';

                  if (areaName) {
                    const period = from ? '（' + from + '〜' + to + '）' : '';
                    leafletLayer.bindPopup(document.createTextNode(areaName + period));
                    leafletLayer.bindTooltip(document.createTextNode(areaName), {
                      sticky: true
                    });
                    historicalFeatures.push({
                      name: areaName,
                      layer: leafletLayer
                    });
                  }

                  leafletLayer.on('click', function () {
                    highlightBoundary(leafletLayer);
                  });
                }
              }).addTo(layers.historical);
            });

            if (featureCount === 0) {
              setLayerStatus('合併前行政界のデータはありません。');
              resetHistoricalNameOptions('データがありません');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers.historical);
              return;
            }

            populateHistoricalNameOptions();
            setLayerStatus('合併前行政界 ' + date + '（' + featureCount + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function (error) {
            if (requestId !== historicalRequestId) {
              return;
            }

            setLayerStatus('合併前行政界のデータを取得できませんでした。');
            resetHistoricalNameOptions('データを取得できませんでした');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.historical);
          });
      }

      document.querySelectorAll('.map-layer-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
          const name = this.dataset.layer;

          if (this.checked) {
            map.addLayer(layers[name]);

            if (name === 'municipalities') {
              fitMapToVisibleLayers();
            } else if (name === 'historical') {
              loadHistoricalLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else if (name === 'small_areas') {
              loadSmallAreaLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else if (name === 'shelters') {
              loadShelterLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else if (name === 'volunteer_centers') {
              loadVolunteerCenterLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else if (name === 'roads') {
              loadRoadLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else if (name === 'waste_sites') {
              loadWasteSiteLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else {
              loadSchoolDistrictLayer(name).then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            }
          } else {
            map.removeLayer(layers[name]);
            fitMapToVisibleLayers();
          }

          saveLayerVisibility();
        });
      });

      const savedVisibility = loadLayerVisibility();

      document.querySelectorAll('.map-layer-toggle').forEach(function (toggle) {
        const name = toggle.dataset.layer;
        const visible = Object.prototype.hasOwnProperty.call(savedVisibility, name)
          ? Boolean(savedVisibility[name])
          : toggle.checked;

        toggle.checked = visible;

        if (visible) {
          map.addLayer(layers[name]);
        } else {
          map.removeLayer(layers[name]);
        }
      });

      const shelterActiveOnlyToggle = document.getElementById('shelter-active-only');

      if (shelterActiveOnlyToggle) {
        try {
          const storedShelterActiveOnly = window.localStorage.getItem(shelterActiveOnlyStorageKey);

          if (storedShelterActiveOnly !== null) {
            shelterActiveOnlyToggle.checked = storedShelterActiveOnly === '1';
          }
        } catch (error) {
        }

        shelterActiveOnlyToggle.addEventListener('change', function () {
          try {
            window.localStorage.setItem(shelterActiveOnlyStorageKey, this.checked ? '1' : '0');
          } catch (error) {
          }

          if (shelterLayerLoaded) {
            applyShelterFilter();
          }
        });
      }

      const historicalDate = document.getElementById('historical-date');
      const historicalDateStorageKey = 'disaster-map-historical-date';

      if (historicalDate) {
        try {
          const storedHistoricalDate = window.localStorage.getItem(historicalDateStorageKey);

          if (storedHistoricalDate && Array.prototype.some.call(historicalDate.options, function (option) {
            return option.value === storedHistoricalDate;
          })) {
            historicalDate.value = storedHistoricalDate;
          }
        } catch (error) {
        }

        historicalDate.addEventListener('change', function () {
          try {
            window.localStorage.setItem(historicalDateStorageKey, this.value);
          } catch (error) {
          }

          const toggle = document.querySelector('.map-layer-toggle[data-layer="historical"]');

          if (toggle && toggle.checked) {
            loadHistoricalLayer();
          }
        });
      }

      const historicalName = document.getElementById('historical-name');

      if (historicalName) {
        historicalName.addEventListener('change', function () {
          const feature = historicalFeatures[Number(this.value)];

          if (!feature || !feature.layer) {
            clearBoundaryHighlight();
            return;
          }

          highlightBoundary(feature.layer);

          if (feature.layer.getBounds) {
            map.fitBounds(feature.layer.getBounds(), {
              padding: [20, 20],
              maxZoom: 14
            });
          }

          feature.layer.openPopup();
        });
      }

      loadMunicipalitiesLayer();

      document.querySelectorAll('.map-layer-toggle').forEach(function (toggle) {
        const name = toggle.dataset.layer;

        if (!toggle.checked || name === 'municipalities') {
          return;
        }

        let loader;

        if (name === 'historical') {
          loader = loadHistoricalLayer();
        } else if (name === 'small_areas') {
          loader = loadSmallAreaLayer();
        } else if (name === 'shelters') {
          loader = loadShelterLayer();
        } else if (name === 'volunteer_centers') {
          loader = loadVolunteerCenterLayer();
        } else if (name === 'roads') {
          loader = loadRoadLayer();
        } else if (name === 'waste_sites') {
          loader = loadWasteSiteLayer();
        } else {
          loader = loadSchoolDistrictLayer(name);
        }

        loader.then(function () {
          fitMapToVisibleLayers();
          window.setTimeout(function () {
            map.invalidateSize();
          }, 100);
        });
      });
    });
  </script>
@endpush
