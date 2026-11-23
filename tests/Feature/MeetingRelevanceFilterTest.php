<?php

namespace Tests\Feature;

use App\Disaster;
use App\Services\DisasterIntelligence\MeetingRelevanceFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingRelevanceFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_meetings_from_other_disasters_are_not_relevant(): void
    {
        $toyama = $this->createDisaster('令和8年8月豪雨');
        $chiba = $this->createDisaster('令和8年8月千葉豪雨');
        $kumamoto = $this->createDisaster('令和8年熊本地震');
        $this->configureRegions($toyama, $chiba, $kumamoto);

        $filter = app(MeetingRelevanceFilter::class);

        $this->assertTrue($filter->isRelevant($toyama->id, '第9回災害対策本部員会議'));
        $this->assertFalse($filter->isRelevant($toyama->id, '令和8年8月千葉豪雨・台風25号情報共有会議'));
        $this->assertFalse($filter->isRelevant($toyama->id, 'くまもと災害ボランティア団体ネットワーク（KVOAD）情報共有会議'));
        $this->assertTrue($filter->isRelevant($toyama->id, '北陸、千葉等の災害に関する会議'));
    }

    public function test_current_disaster_keywords_take_precedence(): void
    {
        $toyama = $this->createDisaster('令和8年8月豪雨');
        $chiba = $this->createDisaster('令和8年8月千葉豪雨');
        $kumamoto = $this->createDisaster('令和8年熊本地震');
        $this->configureRegions($toyama, $chiba, $kumamoto);

        $filter = app(MeetingRelevanceFilter::class);

        $this->assertTrue($filter->isRelevant($chiba->id, '北陸、千葉等の災害に関する会議'));
        $this->assertFalse($filter->isRelevant($chiba->id, 'JVOAD災害対応 令和8年熊本地震'));
        $this->assertFalse($filter->isRelevant($kumamoto->id, '令和8年8月千葉豪雨 情報共有会議'));
        $this->assertTrue($filter->isRelevant($kumamoto->id, '第62回災害対策本部会議'));
    }

    private function createDisaster(string $name): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = $name;
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        return $disaster;
    }

    private function configureRegions(Disaster $toyama, Disaster $chiba, Disaster $kumamoto): void
    {
        config([
            'disaster-intelligence.meeting_region_keywords' => [
                $toyama->id => ['富山県', '北陸'],
                $chiba->id => ['千葉県'],
                $kumamoto->id => ['熊本県', 'KVOAD', 'くまもと', '火の国'],
            ],
        ]);
    }
}
