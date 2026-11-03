<?php

namespace Tests\Feature;

use App\EvacuationShelter;
use App\LocalGovernment;
use App\Prefecture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShelterStatusIngestTest extends TestCase
{
    use RefreshDatabase;

    private function createHimi(): LocalGovernment
    {
        $prefecture = new Prefecture();
        $prefecture->prefecture_code = '16';
        $prefecture->name = '富山県';
        $prefecture->order = 16;
        $prefecture->save();

        $localGovernment = new LocalGovernment();
        $localGovernment->order = 1;
        $localGovernment->prefecture_id = $prefecture->id;
        $localGovernment->prefecture_code = $prefecture->prefecture_code;
        $localGovernment->code = '162051';
        $localGovernment->name = '氷見市';
        $localGovernment->government_type = LocalGovernment::GOVERNMENT_TYPE_TOWN;
        $localGovernment->is_designated_city = false;
        $localGovernment->save();

        return $localGovernment;
    }

    private function createShelter(LocalGovernment $localGovernment, string $name, string $commonId): EvacuationShelter
    {
        return EvacuationShelter::create([
            'local_government_id' => $localGovernment->id,
            'common_id' => $commonId,
            'name' => $name,
            'address' => '富山県氷見市1-1',
            'latitude' => 36.85,
            'longitude' => 136.98,
            'shelter_type' => 'designated_shelter',
            'source_name' => '国土地理院',
            'source_url' => 'https://hinanmap.gsi.go.jp/',
            'fetched_at' => now(),
        ]);
    }

    public function test_ingest_marks_listed_shelters_active_and_closes_others(): void
    {
        $himi = $this->createHimi();
        $active = $this->createShelter($himi, '氷見市立海峰小学校', 'A');
        $closed = $this->createShelter($himi, '氷見市立朝日丘小学校', 'B');
        $alias = $this->createShelter($himi, '氷見市ふれあいスポーツセンター', 'C');

        $this->artisan('shelter-status:ingest', [
            'admin_code' => '162051',
            '--json' => json_encode([
                'status' => 'open',
                'source_name' => '氷見市 避難所情報',
                'source_url' => 'https://www.city.himi.toyama.jp/emergency/9236.html',
                'active' => [
                    ['id' => $active->id, 'evacuees' => 12, 'note' => '開設中'],
                    ['name' => 'ふれスポ', 'evacuees' => 3],
                ],
            ], JSON_UNESCAPED_UNICODE),
        ])->assertExitCode(0);

        $this->assertTrue($active->fresh()->is_active);
        $this->assertSame('open', $active->fresh()->activity_status);
        $this->assertSame(12, $active->fresh()->evacuees);
        $this->assertSame('開設中', $active->fresh()->status_note);
        $this->assertTrue($alias->fresh()->is_active);
        $this->assertSame(3, $alias->fresh()->evacuees);
        $this->assertFalse($closed->fresh()->is_active);
        $this->assertSame('closed', $closed->fresh()->activity_status);
    }

    public function test_ingest_closes_all_shelters_when_source_reports_closed(): void
    {
        $himi = $this->createHimi();
        $shelter = $this->createShelter($himi, '氷見市立海峰小学校', 'A');
        $shelter->update([
            'is_active' => true,
            'activity_status' => 'open',
            'evacuees' => 5,
        ]);

        $this->artisan('shelter-status:ingest', [
            'admin_code' => '162051',
            '--json' => json_encode([
                'status' => 'closed',
                'source_name' => '氷見市 避難所情報',
                'source_url' => 'https://www.city.himi.toyama.jp/emergency/9236.html',
            ], JSON_UNESCAPED_UNICODE),
        ])->assertExitCode(0);

        $this->assertFalse($shelter->fresh()->is_active);
        $this->assertSame('closed', $shelter->fresh()->activity_status);
        $this->assertNull($shelter->fresh()->evacuees);
    }
}
