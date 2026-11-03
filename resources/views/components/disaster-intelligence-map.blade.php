@props([
    'disasterId',
    'height' => 'calc(100vh - 64px)',
])

<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css">

<style>
    .disaster-intel {
        position: relative;
        background: #eef2f5;
    }

    .disaster-intel__filters {
        position: absolute;
        z-index: 5;
        top: 0.75rem;
        left: 0.75rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        max-width: calc(100% - 1.5rem);
        padding: 0.55rem 0.75rem;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #d9dee3;
        border-radius: 0.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
        font-size: 0.8rem;
    }

    .disaster-intel__filters strong {
        margin-right: 0.15rem;
    }

    .disaster-intel__filters label {
        margin: 0;
        white-space: nowrap;
    }

    .disaster-intel__road-panel {
        position: absolute;
        z-index: 5;
        top: 4.35rem;
        left: 0.75rem;
        display: flex;
        flex-direction: column;
        width: 21rem;
        max-height: calc(100% - 7.5rem);
        background: rgba(255, 255, 255, 0.97);
        border: 1px solid #d9dee3;
        border-radius: 0.5rem;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.14);
        overflow: hidden;
    }

    .disaster-intel__road-panel-header {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        padding: 0.6rem 0.75rem;
        border-bottom: 1px solid #e9ecef;
        background: #f8f9fa;
    }

    .disaster-intel__road-panel-header strong {
        color: #212529;
        font-size: 0.82rem;
    }

    .disaster-intel__road-panel-header span {
        color: #6c757d;
        font-size: 0.7rem;
    }

    .disaster-intel__road-list {
        overflow-y: auto;
    }

    .disaster-intel__road-empty {
        margin: 0;
        padding: 0.8rem 0.75rem;
        color: #6c757d;
        font-size: 0.75rem;
    }

    .disaster-intel__road-item {
        display: block;
        width: 100%;
        padding: 0.55rem 0.75rem;
        border: 0;
        border-bottom: 1px solid #f1f3f5;
        background: #fff;
        color: #212529;
        text-align: left;
        cursor: pointer;
    }

    .disaster-intel__road-item:hover,
    .disaster-intel__road-item:focus-visible {
        background: #eef7ff;
        outline: none;
    }

    .disaster-intel__road-item-name {
        display: block;
        margin-top: 0.18rem;
        font-size: 0.79rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .disaster-intel__road-item-section {
        display: block;
        margin-top: 0.15rem;
        color: #6c757d;
        font-size: 0.7rem;
        line-height: 1.35;
    }

    .disaster-intel__road-item-vehicles {
        display: block;
        margin-top: 0.2rem;
        color: #495057;
        font-size: 0.68rem;
    }

    .disaster-intel__badge {
        display: inline-block;
        padding: 0.08rem 0.38rem;
        border-radius: 0.25rem;
        color: #fff;
        font-size: 0.66rem;
        font-weight: 700;
        line-height: 1.4;
        vertical-align: middle;
    }

    .disaster-intel__popup .guidance {
        margin: 0.35rem 0;
        padding: 0.4rem 0.5rem;
        border-radius: 0.3rem;
        background: #f8f9fa;
        font-weight: 600;
    }

    .disaster-intel__map {
        width: 100%;
    }

    @media (max-width: 767.98px) {
        .disaster-intel__road-panel {
            top: auto;
            right: 0.75rem;
            bottom: 4.2rem;
            width: auto;
            max-height: 38%;
        }

        .disaster-intel__filters {
            font-size: 0.72rem;
        }
    }

    .disaster-intel__legend {
        position: absolute;
        z-index: 5;
        right: 0.75rem;
        bottom: 1.8rem;
        padding: 0.5rem 0.65rem;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #d9dee3;
        border-radius: 0.4rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        color: #343a40;
        font-size: 0.72rem;
        line-height: 1.5;
    }

    .disaster-intel__legend span {
        display: inline-block;
        width: 0.75rem;
        height: 0.75rem;
        margin-right: 0.35rem;
        border-radius: 50%;
        vertical-align: -0.1rem;
    }

    .disaster-intel__status {
        position: absolute;
        z-index: 5;
        left: 0.75rem;
        bottom: 1.8rem;
        max-width: calc(100% - 10rem);
        padding: 0.35rem 0.6rem;
        background: rgba(33, 37, 41, 0.78);
        border-radius: 0.35rem;
        color: #fff;
        font-size: 0.72rem;
    }

    .disaster-intel__popup {
        max-width: 22rem;
        color: #212529;
        font-size: 0.78rem;
        line-height: 1.5;
    }

    .disaster-intel__popup h3 {
        margin: 0 0 0.35rem;
        font-size: 0.92rem;
    }

    .disaster-intel__popup .meta {
        color: #6c757d;
        font-size: 0.72rem;
    }

    .disaster-intel__popup .disclaimer {
        margin-top: 0.45rem;
        padding-top: 0.4rem;
        border-top: 1px solid #e9ecef;
        color: #b02a37;
        font-size: 0.7rem;
    }

    .disaster-intel__popup a {
        color: #0d6efd;
        word-break: break-all;
    }
</style>

<div class="disaster-intel" style="height: {{ $height }};">
    <div class="disaster-intel__filters" aria-label="車両サイズで絞り込み">
        <strong>車両:</strong>
        @foreach (config('disaster-intelligence.vehicles', []) as $vehicleKey => $vehicleLabel)
            <label>
                <input type="checkbox" class="intel-vehicle-filter" value="{{ $vehicleKey }}" checked>
                {{ $vehicleLabel }}
            </label>
        @endforeach
    </div>

    <aside class="disaster-intel__road-panel" aria-label="道路規制一覧">
        <div class="disaster-intel__road-panel-header">
            <strong>道路規制</strong>
            <span id="disaster-intelligence-road-count-{{ $disasterId }}">0件</span>
        </div>
        <div id="disaster-intelligence-road-list-{{ $disasterId }}" class="disaster-intel__road-list">
            <p class="disaster-intel__road-empty">道路規制を読み込んでいます...</p>
        </div>
    </aside>

    <div id="disaster-intelligence-map-{{ $disasterId }}" class="disaster-intel__map" style="height: 100%;"></div>

    <div class="disaster-intel__legend" aria-label="凡例">
        <div><span style="background:#dc3545"></span>全面通行止</div>
        <div><span style="background:#fd7e14"></span>片側交互通行</div>
        <div><span style="background:#6f42c1"></span>大型車・重量規制</div>
        <div><span style="background:#198754"></span>仮置場（開設中）</div>
    </div>

    <div id="disaster-intelligence-status-{{ $disasterId }}" class="disaster-intel__status">
        専門支援情報を読み込んでいます...
    </div>
</div>

<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
<script>
(function () {
    const mapId = 'disaster-intelligence-map-{{ $disasterId }}';
    const statusId = 'disaster-intelligence-status-{{ $disasterId }}';
    const roadsUrl = @json(url('/api/v1/disaster/roads'));
    const wasteUrl = @json(url('/api/v1/disaster/waste-sites'));
    const disasterId = @json($disasterId);
    const container = document.getElementById(mapId);
    const status = document.getElementById(statusId);
    const roadList = document.getElementById('disaster-intelligence-road-list-{{ $disasterId }}');
    const roadCount = document.getElementById('disaster-intelligence-road-count-{{ $disasterId }}');
    let allRoadFeatures = [];
    let allWasteFeatures = [];

    if (!container) {
        return;
    }

    const setStatus = function (message) {
        if (status) {
            status.textContent = message;
        }
    };

    const escapeHtml = function (value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const formatDate = function (value) {
        if (!value) {
            return '';
        }

        const date = new Date(value);

        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('ja-JP');
    };

    const roadTypeMeta = function (type) {
        return {
            full_closure: { label: '全面通行止', color: '#dc3545', guidance: 'この区間は通行できません。迂回路を確認してください。' },
            alternating: { label: '片側交互通行', color: '#fd7e14', guidance: '片側交互で通行できます。現地の誘導に従ってください。' },
            large_vehicle: { label: '大型車規制', color: '#6f42c1', guidance: '大型車は通行できません。車両制限を確認してください。' },
            weight_limit: { label: '重量規制', color: '#6f42c1', guidance: '重量制限があります。積載量を確認してください。' }
        }[type] || { label: 'その他規制', color: '#495057', guidance: '現地の規制表示を確認してください。' };
    };

    const vehicleLabels = {
        light_truck: '軽トラ',
        '2t': '2t車',
        '4t': '4t車',
        large: '大型回送車'
    };

    const formatVehicles = function (vehicles) {
        if (!Array.isArray(vehicles) || vehicles.length === 0) {
            return '車両制限なし・不明';
        }

        return vehicles.map(function (vehicle) {
            return vehicleLabels[vehicle] || vehicle;
        }).join(' / ');
    };

    const roadBadge = function (properties) {
        const meta = roadTypeMeta(properties.regulation_type);

        return '<span class="disaster-intel__badge" style="background:' + meta.color + '">' + escapeHtml(meta.label) + '</span>';
    };

    const roadPopupBody = function (properties) {
        const meta = roadTypeMeta(properties.regulation_type);

        return roadBadge(properties)
            + '<div class="guidance" style="border-left:4px solid ' + meta.color + '">' + escapeHtml(meta.guidance) + '</div>'
            + '<div><strong>区間:</strong> ' + escapeHtml(properties.section || '不明') + '</div>'
            + '<div><strong>対象車両:</strong> ' + escapeHtml(formatVehicles(properties.vehicle_classes)) + '</div>'
            + '<div><strong>事由:</strong> ' + escapeHtml(properties.reason || '不明') + '</div>'
            + '<div class="meta">信頼度: ' + escapeHtml(properties.confidence_rank || 'C') + '</div>';
    };

    const map = new maplibregl.Map({
        container: mapId,
        style: {
            version: 8,
            sources: {
                osm: {
                    type: 'raster',
                    tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                    tileSize: 256,
                    attribution: '&copy; OpenStreetMap contributors'
                }
            },
            layers: [
                {
                    id: 'osm',
                    type: 'raster',
                    source: 'osm'
                }
            ]
        },
        center: [140.12, 35.6],
        zoom: 9
    });

    map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

    const popup = new maplibregl.Popup({
        closeButton: true,
        closeOnClick: true,
        maxWidth: '360px'
    });

    const sourceInfo = function (properties) {
        const source = properties.source || {};

        return '<div class="meta">'
            + '出典: ' + escapeHtml(source.org || '不明')
            + (source.url ? '<br><a href="' + escapeHtml(source.url) + '" target="_blank" rel="noopener noreferrer">一次資料を開く</a>' : '')
            + (source.published_at ? '<br>公表日時: ' + escapeHtml(formatDate(source.published_at)) : '')
            + (source.scraped_at ? '<br>取得日時: ' + escapeHtml(formatDate(source.scraped_at)) : '')
            + '</div>';
    };

    const popupHtml = function (title, body, properties) {
        return '<div class="disaster-intel__popup">'
            + '<h3>' + escapeHtml(title) + '</h3>'
            + body
            + sourceInfo(properties)
            + '<div class="disclaimer">' + escapeHtml(properties.disclaimer || '出典の一次情報を確認してください。') + '</div>'
            + '</div>';
    };

    const openRoadPopup = function (properties, lngLat) {
        popup.setLngLat(lngLat)
            .setHTML(popupHtml(properties.road_name || '道路規制', roadPopupBody(properties), properties))
            .addTo(map);
    };

    const roadColor = function () {
        return [
            'match',
            ['get', 'regulation_type'],
            'full_closure', '#dc3545',
            'alternating', '#fd7e14',
            'large_vehicle', '#6f42c1',
            'weight_limit', '#6f42c1',
            '#495057'
        ];
    };

    const wasteColor = function () {
        return [
            'match',
            ['get', 'status'],
            'open', '#198754',
            'closed', '#6c757d',
            '#0d6efd'
        ];
    };

    const selectedVehicles = function () {
        return Array.from(document.querySelectorAll('.intel-vehicle-filter:checked')).map(function (input) {
            return input.value;
        });
    };

    const roadMatchesVehicles = function (properties, selected, allSelected) {
        if (selected.length === 0) {
            return false;
        }

        if (allSelected) {
            return true;
        }

        const classes = Array.isArray(properties.vehicle_classes) ? properties.vehicle_classes : [];

        return classes.length === 0 || selected.some(function (vehicle) {
            return classes.includes(vehicle);
        });
    };

    const featureBounds = function (feature) {
        const geometry = feature.geometry;

        if (!geometry || !geometry.coordinates) {
            return null;
        }

        const bounds = new maplibregl.LngLatBounds();

        if (geometry.type === 'Point') {
            bounds.extend(geometry.coordinates);
        } else if (geometry.type === 'LineString') {
            geometry.coordinates.forEach(function (coordinate) {
                bounds.extend(coordinate);
            });
        } else if (geometry.type === 'MultiLineString') {
            geometry.coordinates.forEach(function (line) {
                line.forEach(function (coordinate) {
                    bounds.extend(coordinate);
                });
            });
        }

        return bounds.isEmpty() ? null : bounds;
    };

    const focusRoad = function (feature) {
        const properties = feature.properties || {};
        const bounds = featureBounds(feature);

        if (bounds) {
            const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
            map.fitBounds(bounds, {
                padding: isMobile
                    ? 60
                    : { top: 90, right: 70, bottom: 90, left: 360 },
                maxZoom: 14,
                duration: 500
            });
        }

        const geometry = feature.geometry || {};
        const coordinates = geometry.type === 'Point'
            ? geometry.coordinates
            : (geometry.coordinates || [])[Math.floor(((geometry.coordinates || []).length - 1) / 2)];

        if (coordinates) {
            openRoadPopup(properties, coordinates);
        }
    };

    const renderRoadList = function (features) {
        if (roadCount) {
            roadCount.textContent = features.length + '件';
        }

        if (!roadList) {
            return;
        }

        if (features.length === 0) {
            roadList.innerHTML = '<p class="disaster-intel__road-empty">条件に合う道路規制はありません。</p>';
            return;
        }

        roadList.innerHTML = '';

        features.forEach(function (feature) {
            const properties = feature.properties || {};
            const meta = roadTypeMeta(properties.regulation_type);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'disaster-intel__road-item';
            button.innerHTML = roadBadge(properties)
                + '<span class="disaster-intel__road-item-name">' + escapeHtml(properties.road_name || '道路名不明') + '</span>'
                + '<span class="disaster-intel__road-item-section">' + escapeHtml(properties.section || '区間不明') + '</span>'
                + '<span class="disaster-intel__road-item-vehicles">対象: ' + escapeHtml(formatVehicles(properties.vehicle_classes)) + '</span>';
            button.setAttribute('aria-label', (properties.road_name || '道路規制') + 'の詳細を表示');
            button.addEventListener('click', function () {
                focusRoad(feature);
            });
            roadList.appendChild(button);
        });
    };

    const updateStatus = function () {
        const closureCount = allRoadFeatures.filter(function (feature) {
            return (feature.properties || {}).regulation_type === 'full_closure';
        }).length;
        const alternatingCount = allRoadFeatures.filter(function (feature) {
            return (feature.properties || {}).regulation_type === 'alternating';
        }).length;

        setStatus('道路規制 ' + allRoadFeatures.length + '件（通行止 ' + closureCount + ' / 片側交互 ' + alternatingCount + '） / 仮置場 ' + allWasteFeatures.length + '件');
    };

    const applyVehicleFilters = function () {
        const selected = selectedVehicles();
        const totalVehicles = document.querySelectorAll('.intel-vehicle-filter').length;
        const allSelected = selected.length === totalVehicles;
        const filteredRoads = allRoadFeatures.filter(function (feature) {
            return roadMatchesVehicles(feature.properties || {}, selected, allSelected);
        });

        if (map.getLayer('roads-line')) {
            let filter = null;

            if (selected.length === 0) {
                filter = ['==', ['get', 'id'], -1];
            } else if (!allSelected) {
                filter = ['any', ['==', ['length', ['get', 'vehicle_classes']], 0]];
                selected.forEach(function (vehicle) {
                    filter.push(['in', vehicle, ['get', 'vehicle_classes']]);
                });
            }

            map.setFilter('roads-line-casing', filter);
            map.setFilter('roads-line', filter);
            map.setFilter('roads-point', filter);
        }

        if (map.getLayer('waste-sites')) {
            if (selected.length === 0) {
                map.setFilter('waste-sites', ['==', ['get', 'id'], -1]);
            } else if (selected.includes('large')) {
                map.setFilter('waste-sites', null);
            } else {
                const maxTons = selected.includes('4t') ? 4 : 2;
                map.setFilter('waste-sites', [
                    '<=',
                    ['coalesce', ['get', 'max_payload_tons'], 0],
                    maxTons
                ]);
            }
        }

        renderRoadList(filteredRoads);
    };

    const fitToData = function (allFeatures) {
        if (!allFeatures.length) {
            return;
        }

        const bounds = new maplibregl.LngLatBounds();

        allFeatures.forEach(function (feature) {
            const geometry = feature.geometry;

            if (!geometry || !geometry.coordinates) {
                return;
            }

            if (geometry.type === 'Point') {
                bounds.extend(geometry.coordinates);
            } else if (geometry.type === 'LineString') {
                geometry.coordinates.forEach(function (coordinate) {
                    bounds.extend(coordinate);
                });
            }
        });

        if (!bounds.isEmpty()) {
            map.fitBounds(bounds, { padding: 60, maxZoom: 13, duration: 0 });
        }
    };

    map.on('load', function () {
        Promise.all([
            fetch(roadsUrl + '?disaster_id=' + encodeURIComponent(disasterId), {
                headers: { 'Accept': 'application/json' }
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('roads ' + response.status);
                }
                return response.json();
            }),
            fetch(wasteUrl + '?disaster_id=' + encodeURIComponent(disasterId), {
                headers: { 'Accept': 'application/json' }
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('waste ' + response.status);
                }
                return response.json();
            })
        ]).then(function (results) {
            const roads = results[0];
            const waste = results[1];
            allRoadFeatures = roads.features || [];
            allWasteFeatures = waste.features || [];

            map.addSource('roads', { type: 'geojson', data: roads });
            map.addSource('waste-sites', { type: 'geojson', data: waste });

            map.addLayer({
                id: 'roads-line-casing',
                type: 'line',
                source: 'roads',
                filter: ['==', ['geometry-type'], 'LineString'],
                paint: {
                    'line-color': '#ffffff',
                    'line-width': 9,
                    'line-opacity': 0.9
                }
            });

            map.addLayer({
                id: 'roads-line',
                type: 'line',
                source: 'roads',
                filter: ['==', ['geometry-type'], 'LineString'],
                paint: {
                    'line-color': roadColor(),
                    'line-width': 5,
                    'line-opacity': 0.95
                }
            });

            map.addLayer({
                id: 'roads-point',
                type: 'circle',
                source: 'roads',
                filter: ['==', ['geometry-type'], 'Point'],
                paint: {
                    'circle-radius': 7,
                    'circle-color': roadColor(),
                    'circle-stroke-color': '#fff',
                    'circle-stroke-width': 2
                }
            });

            map.addLayer({
                id: 'waste-sites',
                type: 'circle',
                source: 'waste-sites',
                paint: {
                    'circle-radius': 9,
                    'circle-color': wasteColor(),
                    'circle-stroke-color': '#fff',
                    'circle-stroke-width': 2
                }
            });

            map.on('click', 'roads-line', function (event) {
                openRoadPopup(event.features[0].properties || {}, event.lngLat);
            });

            map.on('click', 'roads-point', function (event) {
                openRoadPopup(event.features[0].properties || {}, event.lngLat);
            });

            map.on('click', 'waste-sites', function (event) {
                const feature = event.features[0];
                const properties = feature.properties || {};
                const conditions = properties.support_conditions || {};
                const body = '<div>状態: ' + escapeHtml(properties.status || 'unknown') + '</div>'
                    + '<div>受入品目: ' + escapeHtml((properties.accepted_items || []).join('、') || '不明') + '</div>'
                    + '<div>受入不可: ' + escapeHtml((properties.rejected_items || []).join('、') || '不明') + '</div>'
                    + '<div>車両制限: ' + escapeHtml(properties.vehicle_limit || (properties.max_payload_tons ? properties.max_payload_tons + 'tまで' : '不明')) + '</div>'
                    + '<div>搬入条件: ' + escapeHtml(JSON.stringify(conditions)) + '</div>';
                popup.setLngLat(event.lngLat).setHTML(popupHtml(properties.name || '仮置場', body, properties)).addTo(map);
            });

            ['roads-line', 'roads-point', 'waste-sites'].forEach(function (layer) {
                map.on('mouseenter', layer, function () {
                    map.getCanvas().style.cursor = 'pointer';
                });
                map.on('mouseleave', layer, function () {
                    map.getCanvas().style.cursor = '';
                });
            });

            document.querySelectorAll('.intel-vehicle-filter').forEach(function (input) {
                input.addEventListener('change', applyVehicleFilters);
            });

            applyVehicleFilters();
            fitToData(allRoadFeatures.concat(allWasteFeatures));
            updateStatus();
        }).catch(function (error) {
            console.error(error);
            setStatus('専門支援情報を取得できませんでした。');
        });
    });
})();
</script>
