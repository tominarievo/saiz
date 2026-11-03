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

    .disaster-intel__map {
        width: 100%;
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

    <div id="disaster-intelligence-map-{{ $disasterId }}" class="disaster-intel__map" style="height: 100%;"></div>

    <div class="disaster-intel__legend" aria-label="凡例">
        <div><span style="background:#dc3545"></span>全面通行止</div>
        <div><span style="background:#fd7e14"></span>片側交互・車両規制</div>
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

    const applyVehicleFilters = function () {
        const selected = Array.from(document.querySelectorAll('.intel-vehicle-filter:checked')).map(function (input) {
            return input.value;
        });
        const allSelected = selected.length === document.querySelectorAll('.intel-vehicle-filter').length;

        if (map.getLayer('roads-line')) {
            if (allSelected || selected.length === 0) {
                map.setFilter('roads-line', null);
                map.setFilter('roads-point', null);
            } else {
                const filter = ['any', ['==', ['length', ['get', 'vehicle_classes']], 0]];
                selected.forEach(function (vehicle) {
                    filter.push(['in', vehicle, ['get', 'vehicle_classes']]);
                });
                map.setFilter('roads-line', filter);
                map.setFilter('roads-point', filter);
            }
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
            const roadFeatures = roads.features || [];
            const wasteFeatures = waste.features || [];

            map.addSource('roads', { type: 'geojson', data: roads });
            map.addSource('waste-sites', { type: 'geojson', data: waste });

            map.addLayer({
                id: 'roads-line',
                type: 'line',
                source: 'roads',
                filter: ['==', ['geometry-type'], 'LineString'],
                paint: {
                    'line-color': roadColor(),
                    'line-width': 5,
                    'line-opacity': 0.9
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
                const feature = event.features[0];
                const properties = feature.properties || {};
                const body = '<div>区間: ' + escapeHtml(properties.section || '-') + '</div>'
                    + '<div>規制: ' + escapeHtml(properties.regulation_type || '-') + '</div>'
                    + '<div>対象車両: ' + escapeHtml((properties.vehicle_classes || []).join(', ') || '不明') + '</div>'
                    + '<div>事由: ' + escapeHtml(properties.reason || '-') + '</div>'
                    + '<div class="meta">信頼度: ' + escapeHtml(properties.confidence_rank || 'C') + '</div>';
                popup.setLngLat(event.lngLat).setHTML(popupHtml(properties.road_name || '道路規制', body, properties)).addTo(map);
            });

            map.on('click', 'roads-point', function (event) {
                const feature = event.features[0];
                const properties = feature.properties || {};
                const body = '<div>規制: ' + escapeHtml(properties.regulation_type || '-') + '</div>'
                    + '<div>対象車両: ' + escapeHtml((properties.vehicle_classes || []).join(', ') || '不明') + '</div>'
                    + '<div>事由: ' + escapeHtml(properties.reason || '-') + '</div>';
                popup.setLngLat(event.lngLat).setHTML(popupHtml(properties.road_name || '道路規制', body, properties)).addTo(map);
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
            fitToData(roadFeatures.concat(wasteFeatures));
            setStatus('道路規制 ' + roadFeatures.length + '件 / 仮置場 ' + wasteFeatures.length + '件');
        }).catch(function (error) {
            console.error(error);
            setStatus('専門支援情報を取得できませんでした。');
        });
    });
})();
</script>
