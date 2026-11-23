<?php

namespace App\Http\Controllers;

use App\Disaster;
use App\DisasterDataSource;
use App\DisasterInformationLink;
use App\DisasterIntelligenceSource;
use App\MeetingInsight;
use App\RoadRegulation;
use App\Services\CollectedInformationBlocklist;
use App\WasteSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectedInformationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $disasters = Disaster::orderByDesc('event_date')->orderByDesc('id')->get();
        $disasterId = (int) $request->input('disaster_id', $disasters->first()->id ?? 0);
        $disaster = $disasters->firstWhere('id', $disasterId);
        $search = trim((string) $request->input('q', ''));

        if (!$disaster) {
            abort(404);
        }

        $sources = DisasterIntelligenceSource::where('disaster_id', $disaster->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('source_org', 'like', '%' . $search . '%')
                        ->orWhere('url', 'like', '%' . $search . '%')
                        ->orWhere('type', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('last_synced_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $links = DisasterInformationLink::where('disaster_id', $disaster->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', '%' . $search . '%')
                        ->orWhere('url', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('fetched_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $roads = RoadRegulation::where('disaster_id', $disaster->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('road_name', 'like', '%' . $search . '%')
                        ->orWhere('section', 'like', '%' . $search . '%')
                        ->orWhere('reason', 'like', '%' . $search . '%')
                        ->orWhere('source_url', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $wasteSites = WasteSite::where('disaster_id', $disaster->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('source_url', 'like', '%' . $search . '%')
                        ->orWhere('status', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $meetings = MeetingInsight::where('disaster_id', $disaster->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('meeting_name', 'like', '%' . $search . '%')
                        ->orWhere('source_url', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $staleItems = collect()
            ->merge($sources
                ->filter(fn (DisasterIntelligenceSource $source) => $this->isStale($source->published_at ?? $source->last_synced_at ?? $source->updated_at))
                ->map(fn (DisasterIntelligenceSource $source) => [
                    'type' => 'source',
                    'id' => $source->id,
                    'label' => 'AI収集元: ' . ($source->source_org ?: $source->url),
                    'detail' => $source->url,
                    'date' => $source->published_at ?? $source->last_synced_at ?? $source->updated_at,
                ]))
            ->merge($links
                ->filter(fn (DisasterInformationLink $link) => $this->isStale($link->fetched_at ?? $link->updated_at))
                ->map(fn (DisasterInformationLink $link) => [
                    'type' => 'link',
                    'id' => $link->id,
                    'label' => '最新情報: ' . \Illuminate\Support\Str::limit($link->description, 80),
                    'detail' => $link->url,
                    'date' => $link->fetched_at ?? $link->updated_at,
                ]))
            ->merge($roads
                ->filter(fn (RoadRegulation $road) => $this->isStale($road->source_published_at ?? $road->updated_at))
                ->map(fn (RoadRegulation $road) => [
                    'type' => 'road',
                    'id' => $road->id,
                    'label' => '道路: ' . $road->road_name,
                    'detail' => $road->section ?: $road->source_url,
                    'date' => $road->source_published_at ?? $road->updated_at,
                ]))
            ->merge($wasteSites
                ->filter(fn (WasteSite $site) => $this->isStale($site->source_published_at ?? $site->updated_at))
                ->map(fn (WasteSite $site) => [
                    'type' => 'waste_site',
                    'id' => $site->id,
                    'label' => '仮置場: ' . $site->name,
                    'detail' => $site->source_url,
                    'date' => $site->source_published_at ?? $site->updated_at,
                ]))
            ->merge($meetings
                ->filter(fn (MeetingInsight $meeting) => $this->isStale($meeting->source_published_at ?? $meeting->held_at ?? $meeting->updated_at))
                ->map(fn (MeetingInsight $meeting) => [
                    'type' => 'meeting_insight',
                    'id' => $meeting->id,
                    'label' => '会議: ' . $meeting->meeting_name,
                    'detail' => $meeting->source_url,
                    'date' => $meeting->source_published_at ?? $meeting->held_at ?? $meeting->updated_at,
                ]))
            ->sortByDesc(fn (array $item) => $item['date']?->getTimestamp() ?? 0)
            ->values();

        return view('admin.collected-information.index', compact(
            'disasters',
            'disaster',
            'sources',
            'links',
            'roads',
            'wasteSites',
            'meetings',
            'staleItems'
        ));
    }

    private function isStale(mixed $date): bool
    {
        return $date instanceof \DateTimeInterface && $date < now()->subMonth();
    }

    public function destroy(Request $request, string $type, int $id, CollectedInformationBlocklist $blocklist): RedirectResponse
    {
        $this->authorizeAdmin();

        $disasterId = $this->deleteItem($type, $id, (int) ($request->user()->id ?? 0), $blocklist);

        if ($disasterId === null) {
            abort(404);
        }

        return redirect()
            ->route('admin.collected_information.index', ['disaster_id' => $disasterId])
            ->with('status', '削除しました。再収集されません。');
    }

    public function destroyBulk(Request $request, CollectedInformationBlocklist $blocklist): RedirectResponse
    {
        $this->authorizeAdmin();

        $items = $request->input('items', []);

        if (!is_array($items)) {
            $items = [];
        }

        $userId = (int) ($request->user()->id ?? 0);
        $deleted = 0;
        $disasterId = 0;

        foreach ($items as $item) {
            if (!is_string($item) || !str_contains($item, ':')) {
                continue;
            }

            [$type, $id] = explode(':', $item, 2);

            if (!ctype_digit($id)) {
                continue;
            }

            $result = $this->deleteItem($type, (int) $id, $userId, $blocklist);

            if ($result !== null) {
                $deleted++;
                $disasterId = $result;
            }
        }

        if ($deleted === 0) {
            return back()->with('status', '削除対象がありませんでした。');
        }

        return redirect()
            ->route('admin.collected_information.index', ['disaster_id' => $disasterId])
            ->with('status', $deleted . '件を削除しました。再収集されません。');
    }

    private function deleteItem(string $type, int $id, int $userId, CollectedInformationBlocklist $blocklist): ?int
    {
        switch ($type) {
            case 'source':
                $source = DisasterIntelligenceSource::find($id);

                if (!$source) {
                    return null;
                }

                $disasterId = (int) $source->disaster_id;
                $url = (string) $source->url;
                $blocklist->block($disasterId, CollectedInformationBlocklist::TYPE_SOURCE, CollectedInformationBlocklist::normalizeUrl($url), $url, $userId);
                RoadRegulation::where('disaster_id', $disasterId)->where('source_url', $url)->delete();
                WasteSite::where('disaster_id', $disasterId)->where('source_url', $url)->delete();
                MeetingInsight::where('disaster_id', $disasterId)->where('source_url', $url)->delete();
                DisasterDataSource::where('disaster_id', $disasterId)->where('source_url', $url)->delete();
                $source->delete();

                return $disasterId;

            case 'link':
                $link = DisasterInformationLink::find($id);

                if (!$link) {
                    return null;
                }

                $disasterId = (int) $link->disaster_id;
                $url = (string) $link->url;
                $blocklist->block($disasterId, CollectedInformationBlocklist::TYPE_LINK, CollectedInformationBlocklist::normalizeUrl($url), $url, $userId);
                $link->delete();

                return $disasterId;

            case 'road':
                $road = RoadRegulation::find($id);

                if (!$road) {
                    return null;
                }

                $disasterId = (int) $road->disaster_id;
                $blocklist->block(
                    $disasterId,
                    CollectedInformationBlocklist::TYPE_ROAD,
                    CollectedInformationBlocklist::roadKey($road->source_url, $road->road_name, $road->section),
                    $road->source_url,
                    $userId
                );
                $road->delete();

                return $disasterId;

            case 'waste_site':
                $site = WasteSite::find($id);

                if (!$site) {
                    return null;
                }

                $disasterId = (int) $site->disaster_id;
                $blocklist->block(
                    $disasterId,
                    CollectedInformationBlocklist::TYPE_WASTE_SITE,
                    CollectedInformationBlocklist::wasteSiteKey($site->source_url, $site->name),
                    $site->source_url,
                    $userId
                );
                $site->delete();

                return $disasterId;

            case 'meeting_insight':
                $meeting = MeetingInsight::find($id);

                if (!$meeting) {
                    return null;
                }

                $disasterId = (int) $meeting->disaster_id;
                $blocklist->block(
                    $disasterId,
                    CollectedInformationBlocklist::TYPE_MEETING_INSIGHT,
                    CollectedInformationBlocklist::meetingInsightKey($meeting->source_url, $meeting->meeting_name, $meeting->held_at?->toDateTimeString()),
                    $meeting->source_url,
                    $userId
                );
                $meeting->delete();

                return $disasterId;
        }

        return null;
    }
}
