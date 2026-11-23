@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid py-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
                <h1 class="h4 mb-1">収集情報の管理</h1>
                <div class="text-muted small">
                    削除した情報は再収集されません。1か月以上更新がない情報にはマークを表示します。
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center">
                <form method="GET" action="{{ route('admin.collected_information.index') }}" class="form-inline d-flex align-items-center mr-2 mb-1">
                    <select name="disaster_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        @foreach ($disasters as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $disaster->id)>
                                {{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm mr-2" placeholder="路線名・URL・組織で絞り込み" style="width: 240px;">
                    <button type="submit" class="btn btn-sm btn-outline-primary">絞り込み</button>
                    @if (request()->filled('q'))
                        <a href="{{ route('admin.collected_information.index', ['disaster_id' => $disaster->id]) }}" class="btn btn-sm btn-link">解除</a>
                    @endif
                </form>
                <a href="{{ route('disasters.sources', $disaster->id) }}" class="btn btn-sm btn-outline-secondary mb-1">
                    データ出典へ
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success py-2">{{ session('status') }}</div>
        @endif

        <form id="bulk-delete-form" method="POST" action="{{ route('admin.collected_information.bulk_destroy') }}" class="mb-3">
            @csrf
            @method('DELETE')
            <div id="bulk-hidden-items"></div>
            <div class="d-flex flex-wrap align-items-center">
                <label class="mr-3 mb-0">
                    <input type="checkbox" id="bulk-select-all">
                    全選択
                </label>
                <button type="submit" id="bulk-delete-button" class="btn btn-sm btn-danger" disabled>
                    選択した項目を削除
                </button>
                <span class="ml-2 text-muted small"><span id="bulk-selected-count">0</span>件選択</span>
            </div>
        </form>

        @php
            $staleBefore = now()->subMonth();
            $typeLabels = [
                'source' => 'AI収集元',
                'link' => '最新情報',
                'road' => '道路規制',
                'waste_site' => '仮置場',
                'meeting_insight' => '会議情報',
            ];
        @endphp

        <div class="card mb-4 border-warning">
            <div class="card-header font-weight-bold bg-warning-light">
                1か月以上経過した情報
                <span class="badge badge-warning ml-1">{{ $staleItems->count() }}件</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                    <tr>
                        <th style="width: 32px"></th>
                        <th style="width: 12%">種別</th>
                        <th>内容</th>
                        <th>基準日</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($staleItems as $item)
                        <tr>
                            <td><input type="checkbox" class="collected-item-checkbox" value="{{ $item['type'] }}:{{ $item['id'] }}" aria-label="この項目を選択"></td>
                            <td>{{ $typeLabels[$item['type']] ?? $item['type'] }}</td>
                            <td>
                                <div class="font-weight-bold">{{ $item['label'] }}</div>
                                <div class="text-muted small text-break">{{ \Illuminate\Support\Str::limit($item['detail'], 160) }}</div>
                            </td>
                            <td class="text-nowrap">{{ $item['date'] ? $item['date']->format('Y/m/d') : '-' }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.collected_information.destroy', ['type' => $item['type'], 'id' => $item['id']]) }}" onsubmit="return confirm('この情報を削除します。再収集されません。よろしいですか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">1か月以上経過した情報はありません。</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header font-weight-bold">AI収集元</div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                    <tr>
                        <th style="width: 32px"></th>
                        <th>カテゴリ</th>
                        <th>発信組織</th>
                        <th>URL</th>
                        <th>最終同期</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($sources as $source)
                        @php($last = $source->last_synced_at ?? $source->updated_at ?? $source->discovered_at)
                        <tr>
                            <td><input type="checkbox" class="collected-item-checkbox" value="source:{{ $source->id }}" aria-label="この項目を選択"></td>
                            <td>{{ $source->type }}</td>
                            <td>{{ $source->source_org ?: '-' }}</td>
                            <td class="text-break">{{ $source->url }}</td>
                            <td class="text-nowrap">
                                {{ $last ? $last->format('Y/m/d H:i') : '-' }}
                                @if ($last && $last->lt($staleBefore))
                                    <span class="badge badge-warning ml-1">1か月以上経過</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.collected_information.destroy', ['type' => 'source', 'id' => $source->id]) }}" onsubmit="return confirm('このAI収集元と、その出典から取得した情報を削除します。再収集されません。よろしいですか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">登録はありません。</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header font-weight-bold">最新情報リンク</div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                    <tr>
                        <th style="width: 32px"></th>
                        <th>概要</th>
                        <th>URL</th>
                        <th>取得日時</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($links as $link)
                        @php($last = $link->fetched_at ?? $link->updated_at)
                        <tr>
                            <td><input type="checkbox" class="collected-item-checkbox" value="link:{{ $link->id }}" aria-label="この項目を選択"></td>
                            <td>{{ \Illuminate\Support\Str::limit($link->description, 120) }}</td>
                            <td class="text-break">{{ $link->url }}</td>
                            <td class="text-nowrap">
                                {{ $last ? $last->format('Y/m/d H:i') : '-' }}
                                @if ($last && $last->lt($staleBefore))
                                    <span class="badge badge-warning ml-1">1か月以上経過</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.collected_information.destroy', ['type' => 'link', 'id' => $link->id]) }}" onsubmit="return confirm('この最新情報リンクを削除します。再収集されません。よろしいですか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">登録はありません。</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header font-weight-bold">道路規制</div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                    <tr>
                        <th style="width: 32px"></th>
                        <th>路線名</th>
                        <th>区間</th>
                        <th>状態</th>
                        <th>出典</th>
                        <th>更新</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($roads as $road)
                        @php($last = $road->updated_at ?? $road->scraped_at)
                        <tr>
                            <td><input type="checkbox" class="collected-item-checkbox" value="road:{{ $road->id }}" aria-label="この項目を選択"></td>
                            <td>{{ $road->road_name }}</td>
                            <td>{{ $road->section ?: '-' }}</td>
                            <td>{{ $road->status }}</td>
                            <td class="text-break">{{ $road->source_url }}</td>
                            <td class="text-nowrap">
                                {{ $last ? $last->format('Y/m/d H:i') : '-' }}
                                @if ($last && $last->lt($staleBefore))
                                    <span class="badge badge-warning ml-1">1か月以上経過</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.collected_information.destroy', ['type' => 'road', 'id' => $road->id]) }}" onsubmit="return confirm('この道路規制情報を削除します。再収集されません。よろしいですか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">登録はありません。</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header font-weight-bold">仮置場</div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                    <tr>
                        <th style="width: 32px"></th>
                        <th>名称</th>
                        <th>状態</th>
                        <th>出典</th>
                        <th>更新</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($wasteSites as $site)
                        @php($last = $site->updated_at ?? $site->scraped_at)
                        <tr>
                            <td><input type="checkbox" class="collected-item-checkbox" value="waste_site:{{ $site->id }}" aria-label="この項目を選択"></td>
                            <td>{{ $site->name }}</td>
                            <td>{{ $site->status }}</td>
                            <td class="text-break">{{ $site->source_url }}</td>
                            <td class="text-nowrap">
                                {{ $last ? $last->format('Y/m/d H:i') : '-' }}
                                @if ($last && $last->lt($staleBefore))
                                    <span class="badge badge-warning ml-1">1か月以上経過</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.collected_information.destroy', ['type' => 'waste_site', 'id' => $site->id]) }}" onsubmit="return confirm('この仮置場情報を削除します。再収集されません。よろしいですか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">登録はありません。</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header font-weight-bold">会議情報</div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                    <tr>
                        <th style="width: 32px"></th>
                        <th>会議名</th>
                        <th>開催日</th>
                        <th>出典</th>
                        <th>更新</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($meetings as $meeting)
                        @php($last = $meeting->updated_at ?? $meeting->scraped_at)
                        <tr>
                            <td><input type="checkbox" class="collected-item-checkbox" value="meeting_insight:{{ $meeting->id }}" aria-label="この項目を選択"></td>
                            <td>{{ $meeting->meeting_name }}</td>
                            <td>{{ $meeting->held_at ? $meeting->held_at->format('Y/m/d') : '-' }}</td>
                            <td class="text-break">{{ $meeting->source_url }}</td>
                            <td class="text-nowrap">
                                {{ $last ? $last->format('Y/m/d H:i') : '-' }}
                                @if ($last && $last->lt($staleBefore))
                                    <span class="badge badge-warning ml-1">1か月以上経過</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.collected_information.destroy', ['type' => 'meeting_insight', 'id' => $meeting->id]) }}" onsubmit="return confirm('この会議情報を削除します。再収集されません。よろしいですか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">登録はありません。</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('bulk-delete-form');
        var selectAll = document.getElementById('bulk-select-all');
        var button = document.getElementById('bulk-delete-button');
        var count = document.getElementById('bulk-selected-count');
        var hiddenContainer = document.getElementById('bulk-hidden-items');

        if (!form || !button || !count || !hiddenContainer) {
            return;
        }

        function checkboxes() {
            return Array.prototype.slice.call(document.querySelectorAll('.collected-item-checkbox'));
        }

        function selected() {
            return checkboxes().filter(function (checkbox) {
                return checkbox.checked;
            });
        }

        function sync() {
            var items = selected();
            count.textContent = String(items.length);
            button.disabled = items.length === 0;

            if (selectAll) {
                selectAll.checked = items.length > 0 && items.length === checkboxes().length;
            }
        }

        document.addEventListener('change', function (event) {
            if (event.target && event.target.classList.contains('collected-item-checkbox')) {
                sync();
            }
        });

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes().forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
                sync();
            });
        }

        form.addEventListener('submit', function (event) {
            var items = selected();

            if (items.length === 0) {
                event.preventDefault();
                return;
            }

            if (!window.confirm(items.length + '件を削除します。再収集されません。よろしいですか？')) {
                event.preventDefault();
                return;
            }

            hiddenContainer.innerHTML = '';

            items.forEach(function (checkbox) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[]';
                input.value = checkbox.value;
                hiddenContainer.appendChild(input);
            });
        });

        sync();
    })();
</script>
@endpush
