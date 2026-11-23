<?php

namespace Tests\Feature;

use App\LocalGovernment;
use App\Prefecture;
use App\VolunteerCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteerCenterStatusIngestTest extends TestCase
{
    use RefreshDatabase;

    private function createCenter(): VolunteerCenter
    {
        $prefecture = new Prefecture();
        $prefecture->prefecture_code = '12';
        $prefecture->name = '千葉県';
        $prefecture->order = 12;
        $prefecture->save();

        $localGovernment = new LocalGovernment();
        $localGovernment->order = 1;
        $localGovernment->prefecture_id = $prefecture->id;
        $localGovernment->prefecture_code = '12';
        $localGovernment->code = '12100';
        $localGovernment->name = '千葉市';
        $localGovernment->government_type = LocalGovernment::GOVERNMENT_TYPE_TOWN;
        $localGovernment->is_designated_city = false;
        $localGovernment->save();

        return VolunteerCenter::create([
            'local_government_id' => $localGovernment->id,
            'name' => 'テストボランティアセンター',
            'source_name' => '公式サイト',
            'source_url' => 'https://www.chibakenshakyo.or.jp/',
            'activity_status' => 'open',
        ]);
    }

    public function test_ingest_updates_closed_open_and_unknown_status(): void
    {
        $center = $this->createCenter();

        $closed = json_encode([
            'title' => '募集終了',
            'summary' => 'センターは閉所しました。',
            'url' => 'https://www.chibakenshakyo.or.jp/',
            'status' => 'closed',
            'status_note' => '公式サイトに閉所の記載',
        ], JSON_UNESCAPED_UNICODE);

        $this->artisan('volunteer-centers:ingest', [
            'center' => $center->id,
            '--json' => $closed,
        ])->assertExitCode(0);

        $center->refresh();
        $this->assertSame('closed', $center->activity_status);
        $this->assertNotNull($center->status_checked_at);
        $this->assertSame('募集終了', $center->latest_recruitment_title);

        $unknown = json_encode([
            'title' => '募集情報未確認',
            'summary' => '閉所・再開の情報を確認できませんでした。',
            'url' => 'https://www.chibakenshakyo.or.jp/',
            'status' => 'unknown',
            'status_note' => '最新情報を確認できず',
        ], JSON_UNESCAPED_UNICODE);

        $this->artisan('volunteer-centers:ingest', [
            'center' => $center->id,
            '--json' => $unknown,
        ])->assertExitCode(0);

        $center->refresh();
        $this->assertSame('closed', $center->activity_status);
        $this->assertSame('最新情報を確認できず', $center->status_note);

        $open = json_encode([
            'title' => '募集再開',
            'summary' => '活動を再開しました。',
            'url' => 'https://www.chibakenshakyo.or.jp/',
            'status' => 'open',
            'status_note' => '公式サイトで再開を確認',
        ], JSON_UNESCAPED_UNICODE);

        $this->artisan('volunteer-centers:ingest', [
            'center' => $center->id,
            '--json' => $open,
        ])->assertExitCode(0);

        $center->refresh();
        $this->assertSame('open', $center->activity_status);
        $this->assertSame('募集再開', $center->latest_recruitment_title);
        $this->assertSame('公式サイトで再開を確認', $center->status_note);
    }
}
