<?php

namespace Tests\Feature;

use App\Disaster;
use App\LocalGovernment;
use App\Prefecture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DisasterLinksRefreshCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createDisaster(): Disaster
    {
        $prefecture = new Prefecture();
        $prefecture->prefecture_code = '16';
        $prefecture->name = '富山県';
        $prefecture->order = 16;
        $prefecture->save();

        $localGovernment = new LocalGovernment();
        $localGovernment->order = 1;
        $localGovernment->prefecture_id = $prefecture->id;
        $localGovernment->prefecture_code = '16';
        $localGovernment->code = '16205';
        $localGovernment->name = '氷見市';
        $localGovernment->government_type = LocalGovernment::GOVERNMENT_TYPE_TOWN;
        $localGovernment->is_designated_city = false;
        $localGovernment->save();

        $disaster = new Disaster();
        $disaster->name = '最新情報更新テスト災害';
        $disaster->status = true;
        $disaster->npo_col_2 = '令和8年度';
        $disaster->event_date = '2026-08-27';
        $disaster->save();
        $disaster->localGovernments()->sync([$localGovernment->id]);

        $disaster->informationLinks()->create([
            'description' => '手動で登録した公式情報',
            'url' => 'https://example.com/manual',
            'source' => 'manual',
        ]);

        $disaster->informationLinks()->create([
            'description' => '古いAI情報',
            'url' => 'https://example.com/old-ai',
            'source' => 'ai',
            'fetched_at' => now()->subDay(),
        ]);

        return $disaster;
    }

    public function test_context_command_returns_disaster_name_and_municipalities(): void
    {
        $disaster = $this->createDisaster();

        $this->assertSame(0, Artisan::call('disaster-links:context'));
        $context = json_decode(Artisan::output(), true);

        $this->assertSame($disaster->id, $context[0]['id']);
        $this->assertSame('最新情報更新テスト災害', $context[0]['name']);
        $this->assertSame(['富山県 氷見市'], $context[0]['municipalities']);
        $this->assertSame('https://example.com/manual', $context[0]['manual_links'][0]['url']);
    }

    public function test_ingest_command_replaces_ai_links_and_keeps_manual_links(): void
    {
        $disaster = $this->createDisaster();

        $payload = json_encode([
            'links' => [
                [
                    'description' => '新しい公式情報',
                    'url' => 'https://example.com/new-ai',
                ],
                [
                    'description' => '手動リンクと重複',
                    'url' => 'https://example.com/manual',
                ],
                [
                    'description' => '不正なURL',
                    'url' => 'javascript:alert(1)',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $this->assertSame(0, Artisan::call('disaster-links:ingest', [
            'disaster' => $disaster->id,
            '--json' => $payload,
        ]));

        $this->assertDatabaseMissing('disaster_information_links', [
            'url' => 'https://example.com/old-ai',
        ]);
        $this->assertDatabaseHas('disaster_information_links', [
            'url' => 'https://example.com/new-ai',
            'source' => 'ai',
        ]);
        $this->assertDatabaseHas('disaster_information_links', [
            'url' => 'https://example.com/manual',
            'source' => 'manual',
        ]);
        $this->assertSame(
            1,
            $disaster->informationLinks()->where('source', 'ai')->count()
        );
    }

    public function test_ingest_command_limits_ai_links_to_ten(): void
    {
        $disaster = $this->createDisaster();

        $links = [];

        for ($index = 1; $index <= 12; $index++) {
            $links[] = [
                'description' => 'AI情報' . $index,
                'url' => 'https://example.com/ai-' . $index,
            ];
        }

        $this->assertSame(0, Artisan::call('disaster-links:ingest', [
            'disaster' => $disaster->id,
            '--json' => json_encode(['links' => $links], JSON_UNESCAPED_UNICODE),
        ]));

        $this->assertSame(
            10,
            $disaster->informationLinks()->where('source', 'ai')->count()
        );
    }
}
