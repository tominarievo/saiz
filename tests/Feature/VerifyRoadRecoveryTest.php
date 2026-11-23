<?php

namespace Tests\Feature;

use App\Disaster;
use App\RoadRegulation;
use App\Services\DisasterIntelligence\RoadRecoveryVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyRoadRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function createDisaster(): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = '道路復旧確認テスト災害';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        config([
            'disaster-intelligence.recovery_sources.' . $disaster->id => [
                [
                    'name' => 'テスト復旧情報',
                    'url' => 'https://www.pref.chiba.lg.jp/recovery.html',
                    'source_org' => '千葉県',
                ],
            ],
        ]);

        return $disaster;
    }

    public function test_force_updates_road_status_instead_of_deleting(): void
    {
        $disaster = $this->createDisaster();

        $road = RoadRegulation::create([
            'disaster_id' => $disaster->id,
            'road_name' => '国道1号',
            'regulation_type' => 'full_closure',
            'confidence_rank' => 'A',
            'source_org' => '千葉県',
            'source_url' => 'https://www.pref.chiba.lg.jp/road.pdf',
        ]);

        $this->mock(RoadRecoveryVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                0 => [
                    'status' => 'open',
                    'reason' => '通行止め解除',
                    'evidence' => '国道1号の通行止めを解除しました。',
                    'source_url' => 'https://www.pref.chiba.lg.jp/recovery.html',
                ],
            ]);
        });

        $this->artisan('disaster-intelligence:verify-roads', [
            'disaster' => $disaster->id,
            '--force' => true,
        ])->assertExitCode(0);

        $road->refresh();

        $this->assertSame('open', $road->status);
        $this->assertNotNull($road->recovered_at);
        $this->assertNotNull($road->last_verified_at);
        $this->assertSame('https://www.pref.chiba.lg.jp/recovery.html', $road->recovery_source_url);
        $this->assertSame('国道1号の通行止めを解除しました。', $road->recovery_evidence);
    }

    public function test_force_updates_partial_regulation(): void
    {
        $disaster = $this->createDisaster();

        $road = RoadRegulation::create([
            'disaster_id' => $disaster->id,
            'road_name' => '県道2号',
            'regulation_type' => 'alternating',
            'confidence_rank' => 'A',
            'source_org' => '千葉県',
            'source_url' => 'https://www.pref.chiba.lg.jp/road2.pdf',
        ]);

        $this->mock(RoadRecoveryVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                0 => [
                    'status' => 'partial',
                    'reason' => '片側交互通行',
                    'evidence' => '片側交互通行で規制中。',
                    'source_url' => 'https://www.pref.chiba.lg.jp/recovery.html',
                ],
            ]);
        });

        $this->artisan('disaster-intelligence:verify-roads', [
            'disaster' => $disaster->id,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertSame('partial', $road->refresh()->status);
    }

    public function test_dry_run_keeps_road_status_unchanged(): void
    {
        $disaster = $this->createDisaster();

        $road = RoadRegulation::create([
            'disaster_id' => $disaster->id,
            'road_name' => '国道2号',
            'regulation_type' => 'full_closure',
            'confidence_rank' => 'A',
            'source_org' => '千葉県',
            'source_url' => 'https://www.pref.chiba.lg.jp/road3.pdf',
        ]);

        $this->mock(RoadRecoveryVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                0 => [
                    'status' => 'open',
                    'reason' => '通行止め解除',
                    'evidence' => '解除済み。',
                    'source_url' => 'https://www.pref.chiba.lg.jp/recovery.html',
                ],
            ]);
        });

        $this->artisan('disaster-intelligence:verify-roads', [
            'disaster' => $disaster->id,
        ])->assertExitCode(0);

        $road->refresh();

        $this->assertSame('closed', $road->status);
        $this->assertNull($road->last_verified_at);
    }
}
