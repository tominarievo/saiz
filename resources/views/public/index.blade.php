@extends('layouts.map')

@section('title', '災害情報 | 災図')

@php($firstDisaster = $disastersData->first())

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

    .public-map-app {
      position: fixed;
      inset: 0;
      overflow: hidden;
      background: #dee2e6;
    }

    #public-disaster-map {
      position: absolute;
      inset: 0;
      z-index: 0;
    }

    .public-map-topbar {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1200;
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.65rem;
      min-height: 56px;
      padding: 0.4rem 0.85rem;
      background: rgba(255, 255, 255, 0.97);
      box-shadow: 0 1px 8px rgba(0, 0, 0, 0.16);
      backdrop-filter: blur(6px);
    }

    .public-map-brand {
      display: inline-flex;
      align-items: center;
      flex-shrink: 0;
    }

    .public-map-brand img {
      width: 34px;
      height: 34px;
      object-fit: contain;
      opacity: 0.9;
    }

    .public-map-title {
      flex-shrink: 0;
      font-size: 0.98rem;
      font-weight: 700;
      color: #212529;
    }

    .public-map-selector {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      min-width: 0;
      flex: 1 1 260px;
    }

    .public-map-selector label {
      margin: 0;
      color: #6c757d;
      font-size: 0.78rem;
      white-space: nowrap;
    }

    .public-map-selector select {
      width: 100%;
      max-width: 420px;
      min-width: 0;
      height: 34px;
      padding: 0 2rem 0 0.6rem;
      color: #212529;
      background: #fff;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
      font-size: 0.9rem;
    }

    .public-map-actions {
      display: flex;
      align-items: center;
      gap: 0.15rem;
      margin-left: auto;
      flex-shrink: 0;
    }

    .public-map-action {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.4rem 0.55rem;
      color: #1f3c88;
      border-radius: 0.375rem;
      font-size: 0.85rem;
      text-decoration: none;
      white-space: nowrap;
    }

    .public-map-action:hover,
    .public-map-action:focus-visible {
      color: #12285f;
      background: #eef2f9;
      text-decoration: none;
      outline: none;
    }

    .public-map-panel {
      position: absolute;
      top: 72px;
      left: 16px;
      z-index: 1100;
      width: 350px;
      max-width: calc(100% - 32px);
      max-height: calc(100vh - 96px);
      overflow-y: auto;
      background: #fff;
      border-radius: 0.5rem;
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.2);
    }

    .public-map-panel-head {
      padding: 1rem 1.1rem 0.8rem;
      border-bottom: 1px solid #e9ecef;
    }

    .public-map-panel-eyebrow {
      margin-bottom: 0.25rem;
      color: #6c757d;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .public-map-panel-title {
      margin: 0 0 0.5rem;
      font-size: 1.15rem;
      font-weight: 700;
      line-height: 1.3;
      color: #212529;
    }

    .public-map-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 0.3rem;
    }

    .public-map-panel-body {
      padding: 0.9rem 1.1rem 1.1rem;
    }

    .public-map-detail {
      display: grid;
      gap: 0.45rem;
      margin-bottom: 0.8rem;
    }

    .public-map-detail > div {
      display: grid;
      grid-template-columns: 6.5rem minmax(0, 1fr);
      gap: 0.5rem;
      align-items: start;
    }

    .public-map-detail dt {
      color: #6c757d;
      font-size: 0.78rem;
      font-weight: 400;
    }

    .public-map-detail dd {
      margin: 0;
      color: #212529;
      font-size: 0.85rem;
      word-break: break-word;
    }

    .public-map-counts {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.35rem;
      margin-bottom: 0.8rem;
      color: #495057;
      font-size: 0.78rem;
    }

    .public-map-counts span {
      padding: 0.3rem 0.45rem;
      background: #f8f9fa;
      border-radius: 0.25rem;
    }

    .public-map-links {
      display: flex;
      flex-wrap: wrap;
      gap: 0.35rem;
      margin-bottom: 1rem;
    }

    .public-map-support {
      margin-bottom: 1rem;
      padding: 0.7rem;
      background: #f8f9fa;
      border-radius: 0.375rem;
    }

    .public-map-support-title {
      margin-bottom: 0.45rem;
      color: #495057;
      font-size: 0.78rem;
      font-weight: 700;
    }

    .public-map-support-row {
      display: flex;
      flex-wrap: wrap;
      gap: 0.4rem;
    }

    .public-map-support-item {
      padding: 0.25rem 0.45rem;
      background: #fff;
      border: 1px solid #dee2e6;
      border-radius: 0.25rem;
      font-size: 0.75rem;
    }

    .public-map-news {
      padding-top: 0.9rem;
      border-top: 1px solid #e9ecef;
    }

    .public-map-news h2 {
      margin: 0 0 0.65rem;
      color: #495057;
      font-size: 0.85rem;
      font-weight: 700;
    }

    .public-map-news-item {
      padding-bottom: 0.65rem;
      margin-bottom: 0.65rem;
      border-bottom: 1px solid #f1f3f5;
    }

    .public-map-news-item:last-child {
      padding-bottom: 0;
      margin-bottom: 0;
      border-bottom: 0;
    }

    .public-map-news-date {
      color: #6c757d;
      font-size: 0.72rem;
    }

    .public-map-news-title {
      margin: 0.1rem 0;
      color: #212529;
      font-size: 0.84rem;
      font-weight: 700;
    }

    .public-map-news-body {
      color: #6c757d;
      font-size: 0.78rem;
    }

    .public-map-status {
      position: absolute;
      left: 50%;
      bottom: 18px;
      z-index: 1300;
      display: none;
      max-width: min(90vw, 32rem);
      padding: 0.45rem 0.8rem;
      transform: translateX(-50%);
      color: #fff;
      background: rgba(33, 37, 41, 0.85);
      border-radius: 1rem;
      font-size: 0.8rem;
      text-align: center;
    }

    .public-map-status.is-visible {
      display: block;
    }

    .public-map-legend {
      position: absolute;
      top: 72px;
      right: 12px;
      z-index: 1100;
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
      padding: 0.5rem 0.65rem;
      background: rgba(255, 255, 255, 0.94);
      border-radius: 0.375rem;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
      color: #495057;
      font-size: 0.75rem;
    }

    .public-map-legend span {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }

    .public-map-legend-dot {
      display: inline-block;
      width: 0.7rem;
      height: 0.7rem;
      border: 1px solid #fff;
      border-radius: 50%;
    }

    .public-map-legend-dot.shelters {
      background: #28a745;
    }

    .public-map-legend-dot.volunteers {
      background: #6f42c1;
    }

    .public-map-legend-dot.roads {
      background: #dc3545;
    }

    .public-map-legend-dot.waste {
      background: #fd7e14;
    }

    @media (max-width: 767.98px) {
      .public-map-title {
        display: none;
      }

      .public-map-selector {
        flex-basis: 100%;
        order: 3;
      }

      .public-map-selector select {
        max-width: none;
      }

      .public-map-actions {
        margin-left: auto;
      }

      .public-map-action span {
        display: none;
      }

      .public-map-panel {
        top: auto;
        bottom: 8px;
        left: 8px;
        right: 8px;
        width: auto;
        max-width: none;
        max-height: 40vh;
      }

      .public-map-legend {
        display: none;
      }

      .public-map-status {
        top: 72px;
        bottom: auto;
      }
    }
  </style>
@endpush

@section('content')
  <div class="public-map-app">
    <div id="public-disaster-map" role="application" aria-label="災害情報地図"></div>

    <header class="public-map-topbar">
      <a href="{{ url('/') }}" class="public-map-brand" aria-label="災図 トップ">
        <img src="{{ asset('img/logo2.png') }}" alt="災図">
      </a>

      <div class="public-map-title">災害情報</div>

      <div class="public-map-selector">
        <label for="disaster-select">災害を選択</label>
        <select id="disaster-select" aria-label="災害を選択">
          @forelse ($disastersData as $disaster)
            <option value="{{ $disaster['id'] }}">
              {{ $disaster['name'] }}@if (filled($disaster['event_date']))（{{ $disaster['event_date'] }}）@endif
            </option>
          @empty
            <option value="">公開中の災害はありません</option>
          @endforelse
        </select>
      </div>

      <div class="public-map-actions">
        <a href="{{ route('terms') }}" class="public-map-action">
          <i class="fas fa-file-alt"></i><span>利用規約</span>
        </a>
        @auth
          @if (auth()->user()->isAdmin())
            <a href="{{ route('admin.collected_information.index') }}" class="public-map-action">
              <i class="fas fa-sliders-h"></i><span>管理</span>
            </a>
          @endif
        @endauth
        <a href="{{ route('login') }}" class="public-map-action">
          <i class="fas fa-sign-in-alt"></i><span>ログイン</span>
        </a>
        @if (config('signup.enabled'))
          <a href="{{ route('signup.create') }}" class="btn btn-primary btn-sm">新規登録</a>
        @endif
      </div>
    </header>

    <aside class="public-map-panel" aria-live="polite">
      <div class="public-map-panel-head">
        <div class="public-map-panel-eyebrow">災害情報</div>
        <h1 class="public-map-panel-title" id="public-disaster-name">
          {{ $firstDisaster['name'] ?? '災害情報' }}
        </h1>
        <div class="public-map-badges" id="public-disaster-badges">
          @if ($firstDisaster)
            @if ($firstDisaster['is_catastrophic_disaster'])
              <span class="badge badge-danger">激甚災害</span>
            @endif
            @if (filled($firstDisaster['year_label']))
              <span class="badge badge-light border">{{ $firstDisaster['year_label'] }}</span>
            @endif
            @if (filled($firstDisaster['event_date']))
              <span class="badge badge-light border">{{ $firstDisaster['event_date'] }}</span>
            @endif
          @endif
        </div>
      </div>

      <div class="public-map-panel-body">
        <dl class="public-map-detail">
          <div>
            <dt>発生年月日</dt>
            <dd id="public-disaster-date">{{ $firstDisaster['event_date'] ?? '-' }}</dd>
          </div>
          <div>
            <dt>年度</dt>
            <dd id="public-disaster-year">{{ $firstDisaster['year_label'] ?? '-' }}</dd>
          </div>
          <div>
            <dt>対象市町村</dt>
            <dd id="public-disaster-municipalities">
              @if ($firstDisaster && $firstDisaster['municipalities']->isNotEmpty())
                {{ $firstDisaster['municipalities']->pluck('name')->implode('、') }}
              @else
                -
              @endif
            </dd>
          </div>
        </dl>

        <div class="public-map-counts" id="public-disaster-counts">
          <span>避難所: -</span>
          <span>ボランティア: -</span>
          <span>道路規制: -</span>
          <span>仮置場: -</span>
        </div>

        <div class="public-map-links">
          @if ($firstDisaster)
            <a href="{{ $firstDisaster['detail_url'] }}" class="btn btn-primary btn-sm" id="public-disaster-detail-link">
              災害詳細を開く
            </a>
            <a href="{{ $firstDisaster['intelligence_url'] }}" class="btn btn-outline-primary btn-sm" id="public-disaster-intelligence-link">
              専門支援情報
            </a>
            <a href="{{ $firstDisaster['sources_url'] }}" class="btn btn-outline-secondary btn-sm" id="public-disaster-sources-link">
              データ出典
            </a>
          @endif
        </div>

        @if (config('public.show_support_status', false) && !empty($signalCounts))
          <div class="public-map-support">
            <div class="public-map-support-title">本日の支援先状況</div>
            <div class="public-map-support-row">
              @foreach ($signalCounts as $signal)
                <span class="public-map-support-item">
                  {{ $signal['label'] }}: <strong>{{ $signal['count'] }}</strong>
                </span>
              @endforeach
            </div>
          </div>
        @endif

        <div class="public-map-news">
          <h2>最新のお知らせ</h2>
          @forelse ($information as $item)
            <div class="public-map-news-item">
              <div class="public-map-news-date">{{ $item->published_at?->format('Y/m/d') }}</div>
              <div class="public-map-news-title">{{ $item->title }}</div>
              <div class="public-map-news-body">
                {{ \Illuminate\Support\Str::limit(strip_tags($item->content ?? ''), 120) }}
              </div>
            </div>
          @empty
            <p class="text-muted small mb-0">現在、公開中のお知らせはありません。</p>
          @endforelse
        </div>
      </div>
    </aside>

    <div class="public-map-legend" aria-hidden="true">
      <span><i class="public-map-legend-dot shelters"></i>避難所</span>
      <span><i class="public-map-legend-dot volunteers"></i>ボランティア</span>
      <span><i class="public-map-legend-dot roads"></i>道路規制</span>
      <span><i class="public-map-legend-dot waste"></i>仮置場</span>
    </div>

    <div id="public-map-status" class="public-map-status" role="status" aria-live="polite"></div>
  </div>
@endsection

@push('scripts')
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    (function () {
      const disasters = @json($disastersData);
      const mapElement = document.getElementById('public-disaster-map');
      const select = document.getElementById('disaster-select');
      const statusElement = document.getElementById('public-map-status');

      if (!mapElement) {
        return;
      }

      const map = L.map(mapElement, {
        scrollWheelZoom: true,
        zoomControl: false
      });

      L.control.zoom({ position: 'bottomright' }).addTo(map);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      map.attributionControl.addAttribution(
        '行政区域: <a href="https://geolonia.github.io/japanese-admins/" target="_blank" rel="noopener">Geolonia 行政区域データ</a>'
      );
      map.attributionControl.addAttribution(
        '避難所: <a href="https://hinanmap.gsi.go.jp/" target="_blank" rel="noopener">国土地理院</a>'
      );

      const layers = {
        municipalities: L.featureGroup().addTo(map),
        shelters: L.featureGroup().addTo(map),
        volunteer_centers: L.featureGroup().addTo(map),
        roads: L.featureGroup().addTo(map),
        waste_sites: L.featureGroup().addTo(map)
      };

      const counts = {
        shelters: 0,
        volunteer_centers: 0,
        roads: 0,
        waste_sites: 0
      };

      let requestId = 0;

      function setStatus(message) {
        if (!statusElement) {
          return;
        }

        statusElement.textContent = message || '';
        statusElement.classList.toggle('is-visible', Boolean(message));
      }

      function appendText(container, text, color, fontWeight) {
        if (!text) {
          return;
        }

        const element = document.createElement('div');
        element.textContent = text;

        if (color) {
          element.style.color = color;
        }

        if (fontWeight) {
          element.style.fontWeight = fontWeight;
        }

        container.appendChild(element);
      }

      function appendLink(container, url, label) {
        if (!url || !/^https?:\/\//i.test(url)) {
          return;
        }

        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = label;
        container.appendChild(link);
      }

      function renderPanel(disaster) {
        const nameElement = document.getElementById('public-disaster-name');
        const badgesElement = document.getElementById('public-disaster-badges');
        const dateElement = document.getElementById('public-disaster-date');
        const yearElement = document.getElementById('public-disaster-year');
        const municipalitiesElement = document.getElementById('public-disaster-municipalities');
        const detailLink = document.getElementById('public-disaster-detail-link');
        const intelligenceLink = document.getElementById('public-disaster-intelligence-link');
        const sourcesLink = document.getElementById('public-disaster-sources-link');

        if (nameElement) {
          nameElement.textContent = disaster.name || '災害情報';
        }

        if (badgesElement) {
          badgesElement.innerHTML = '';

          if (disaster.is_catastrophic_disaster) {
            const badge = document.createElement('span');
            badge.className = 'badge badge-danger';
            badge.textContent = '激甚災害';
            badgesElement.appendChild(badge);
          }

          if (disaster.year_label) {
            const badge = document.createElement('span');
            badge.className = 'badge badge-light border';
            badge.textContent = disaster.year_label;
            badgesElement.appendChild(badge);
          }

          if (disaster.event_date) {
            const badge = document.createElement('span');
            badge.className = 'badge badge-light border';
            badge.textContent = disaster.event_date;
            badgesElement.appendChild(badge);
          }
        }

        if (dateElement) {
          dateElement.textContent = disaster.event_date || '-';
        }

        if (yearElement) {
          yearElement.textContent = disaster.year_label || '-';
        }

        if (municipalitiesElement) {
          const names = (disaster.municipalities || []).map(function (municipality) {
            return municipality.name;
          }).filter(Boolean);

          municipalitiesElement.textContent = names.length > 0 ? names.join('、') : '-';
        }

        if (detailLink && disaster.detail_url) {
          detailLink.href = disaster.detail_url;
        }

        if (intelligenceLink && disaster.intelligence_url) {
          intelligenceLink.href = disaster.intelligence_url;
        }

        if (sourcesLink && disaster.sources_url) {
          sourcesLink.href = disaster.sources_url;
        }
      }

      function renderCounts() {
        const element = document.getElementById('public-disaster-counts');

        if (!element) {
          return;
        }

        element.innerHTML = '';
        [
          ['避難所', counts.shelters],
          ['ボランティア', counts.volunteer_centers],
          ['道路規制', counts.roads],
          ['仮置場', counts.waste_sites]
        ].forEach(function (item) {
          const span = document.createElement('span');
          span.textContent = item[0] + ': ' + item[1];
          element.appendChild(span);
        });
      }

      function clearLayers() {
        Object.keys(layers).forEach(function (key) {
          layers[key].clearLayers();
        });

        counts.shelters = 0;
        counts.volunteer_centers = 0;
        counts.roads = 0;
        counts.waste_sites = 0;
        renderCounts();
      }

      function fitVisibleLayers() {
        const bounds = L.latLngBounds([]);

        Object.keys(layers).forEach(function (key) {
          layers[key].eachLayer(function (layer) {
            if (typeof layer.getBounds === 'function') {
              const layerBounds = layer.getBounds();

              if (layerBounds.isValid()) {
                bounds.extend(layerBounds);
              }
            } else if (typeof layer.getLatLng === 'function') {
              bounds.extend(layer.getLatLng());
            }
          });
        });

        if (bounds.isValid()) {
          map.fitBounds(bounds.pad(0.08), { maxZoom: 13 });
        } else {
          map.setView([37.1, 136.8], 5);
        }
      }

      function loadMunicipalities(disaster, currentRequestId) {
        const requests = [];

        (disaster.municipalities || []).forEach(function (municipality) {
          (municipality.codes || []).forEach(function (code) {
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
                  if (currentRequestId !== requestId) {
                    return;
                  }

                  L.geoJSON(geojson, {
                    style: {
                      color: '#1f3c88',
                      weight: 2,
                      fillColor: '#1f3c88',
                      fillOpacity: 0.06
                    },
                    onEachFeature: function (feature, layer) {
                      layer.bindPopup(municipality.name);
                    }
                  }).addTo(layers.municipalities);
                })
                .catch(function () {
                  // 取得できない区域はスキップする
                })
            );
          });
        });

        return Promise.all(requests);
      }

      function loadShelters(disaster, currentRequestId) {
        return fetch(disaster.evacuation_shelters_url, { headers: { Accept: 'application/json' } })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }

            return response.json();
          })
          .then(function (data) {
            if (currentRequestId !== requestId) {
              return;
            }

            (data.shelters || []).forEach(function (shelter) {
              const latitude = Number(shelter.latitude);
              const longitude = Number(shelter.longitude);

              if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return;
              }

              const marker = L.circleMarker([latitude, longitude], {
                radius: 6,
                color: '#fff',
                weight: 1,
                fillColor: shelter.is_active ? '#28a745' : '#6c757d',
                fillOpacity: 0.95
              });

              const popup = document.createElement('div');
              const title = document.createElement('strong');
              title.textContent = shelter.name || '避難所';
              popup.appendChild(title);

              if (shelter.is_active) {
                appendText(popup, '開設中', '#dc3545', 'bold');
              }

              appendText(popup, shelter.shelter_type === 'emergency_site' ? '指定緊急避難場所' : '指定避難所');

              if (shelter.municipality) {
                appendText(popup, shelter.municipality);
              }

              if (shelter.address) {
                appendText(popup, shelter.address);
              }

              if (shelter.is_active && shelter.evacuees !== null && shelter.evacuees !== undefined) {
                appendText(popup, '避難者数: ' + Number(shelter.evacuees).toLocaleString('ja-JP') + '人');
              }

              if (shelter.status_source && shelter.status_source.url) {
                appendLink(popup, shelter.status_source.url, '出典を見る');
              }

              marker.bindPopup(popup);
              marker.addTo(layers.shelters);
              counts.shelters += 1;
            });

            renderCounts();
          })
          .catch(function () {
            // 避難所データがない場合は地図のみ表示する
          });
      }

      function loadVolunteerCenters(disaster, currentRequestId) {
        return fetch(disaster.volunteer_centers_url, { headers: { Accept: 'application/json' } })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }

            return response.json();
          })
          .then(function (data) {
            if (currentRequestId !== requestId) {
              return;
            }

            (data.centers || []).forEach(function (center) {
              if (center.activity_status === 'closed') {
                return;
              }

              const latitude = Number(center.latitude);
              const longitude = Number(center.longitude);

              if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return;
              }

              const marker = L.circleMarker([latitude, longitude], {
                radius: 7,
                color: '#fff',
                weight: 1,
                fillColor: '#6f42c1',
                fillOpacity: 0.95
              });

              const popup = document.createElement('div');
              const title = document.createElement('strong');
              title.textContent = center.name || 'ボランティアセンター';
              popup.appendChild(title);
              appendText(popup, '災害ボランティアセンター');

              if (center.address) {
                appendText(popup, center.address);
              }

              if (center.phone) {
                appendText(popup, 'TEL: ' + center.phone);
              }

              const recruitment = center.latest_recruitment || {};

              if (recruitment.title) {
                appendText(popup, '最新の募集: ' + recruitment.title, '#495057', 'bold');
              }

              if (recruitment.summary) {
                appendText(popup, recruitment.summary);
              }

              if (recruitment.url) {
                appendLink(popup, recruitment.url, '募集情報の詳細');
              }

              marker.bindPopup(popup);
              marker.addTo(layers.volunteer_centers);
              counts.volunteer_centers += 1;
            });

            renderCounts();
          })
          .catch(function () {
            // ボランティア情報がない場合は地図のみ表示する
          });
      }

      function roadStyle(properties) {
        const type = properties.regulation_type;

        if (type === 'full_closure') {
          return { color: '#dc3545', weight: 5, opacity: 0.9 };
        }

        if (type === 'alternating') {
          return { color: '#fd7e14', weight: 5, opacity: 0.9 };
        }

        if (type === 'large_vehicle' || type === 'weight_limit') {
          return { color: '#0d6efd', weight: 5, opacity: 0.9 };
        }

        return { color: '#6c757d', weight: 4, opacity: 0.85 };
      }

      function loadRoads(disaster, currentRequestId) {
        return fetch(disaster.roads_url, { headers: { Accept: 'application/json' } })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }

            return response.json();
          })
          .then(function (data) {
            if (currentRequestId !== requestId) {
              return;
            }

            L.geoJSON(data, {
              style: function (feature) {
                return roadStyle(feature.properties || {});
              },
              pointToLayer: function (feature, latlng) {
                return L.circleMarker(latlng, {
                  radius: 6,
                  color: '#fff',
                  weight: 1,
                  fillColor: '#dc3545',
                  fillOpacity: 0.95
                });
              },
              onEachFeature: function (feature, layer) {
                const properties = feature.properties || {};
                const popup = document.createElement('div');
                const title = document.createElement('strong');
                title.textContent = properties.road_name || '道路規制';
                popup.appendChild(title);
                appendText(popup, properties.section || '');
                appendText(popup, properties.reason || '');
                appendLink(popup, properties.source && properties.source.url, '出典を見る');
                layer.bindPopup(popup);
              }
            }).addTo(layers.roads);

            counts.roads = (data.features || []).length;
            renderCounts();
          })
          .catch(function () {
            // 道路規制がない場合は地図のみ表示する
          });
      }

      function loadWasteSites(disaster, currentRequestId) {
        return fetch(disaster.waste_sites_url, { headers: { Accept: 'application/json' } })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }

            return response.json();
          })
          .then(function (data) {
            if (currentRequestId !== requestId) {
              return;
            }

            L.geoJSON(data, {
              pointToLayer: function (feature, latlng) {
                return L.circleMarker(latlng, {
                  radius: 7,
                  color: '#fff',
                  weight: 1,
                  fillColor: '#fd7e14',
                  fillOpacity: 0.95
                });
              },
              onEachFeature: function (feature, layer) {
                const properties = feature.properties || {};
                const popup = document.createElement('div');
                const title = document.createElement('strong');
                title.textContent = properties.name || '仮置場';
                popup.appendChild(title);

                if (properties.status) {
                  appendText(popup, properties.status === 'open' ? '受入中' : properties.status);
                }

                if (Array.isArray(properties.accepted_items) && properties.accepted_items.length > 0) {
                  appendText(popup, '受入品目: ' + properties.accepted_items.join('、'));
                }

                if (Array.isArray(properties.rejected_items) && properties.rejected_items.length > 0) {
                  appendText(popup, '受入不可: ' + properties.rejected_items.join('、'));
                }

                appendLink(popup, properties.source && properties.source.url, '出典を見る');
                layer.bindPopup(popup);
              }
            }).addTo(layers.waste_sites);

            counts.waste_sites = (data.features || []).length;
            renderCounts();
          })
          .catch(function () {
            // 仮置場がない場合は地図のみ表示する
          });
      }

      function loadDisaster(disasterId) {
        const disaster = disasters.find(function (item) {
          return Number(item.id) === Number(disasterId);
        });

        if (!disaster) {
          return;
        }

        const currentRequestId = ++requestId;

        clearLayers();
        renderPanel(disaster);
        setStatus('読み込み中...');

        const requests = [
          loadMunicipalities(disaster, currentRequestId),
          loadShelters(disaster, currentRequestId),
          loadVolunteerCenters(disaster, currentRequestId),
          loadRoads(disaster, currentRequestId),
          loadWasteSites(disaster, currentRequestId)
        ];

        Promise.allSettled(requests).then(function () {
          if (currentRequestId !== requestId) {
            return;
          }

          fitVisibleLayers();
          setStatus('');
        });
      }

      if (disasters.length === 0) {
        map.setView([37.1, 136.8], 5);
        setStatus('公開中の災害情報はありません。');

        if (select) {
          select.disabled = true;
        }

        return;
      }

      if (select) {
        select.value = String(disasters[0].id);
        select.addEventListener('change', function () {
          loadDisaster(select.value);
        });
      }

      loadDisaster(disasters[0].id);
    })();
  </script>
@endpush
