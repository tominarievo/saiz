<?php

namespace Tests\Feature;

use App\Services\DisasterIntelligence\DisasterSourceParser;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DisasterSourceParserRedactionTest extends TestCase
{
    public function test_llm_prompt_does_not_contain_raw_personal_information_for_non_road_sources(): void
    {
        config([
            'disaster-intelligence.llm.base_url' => 'https://llm.test/v1',
            'disaster-intelligence.llm.api_key' => 'test-key',
            'disaster-intelligence.llm.retries' => 1,
            'disaster-intelligence.redaction.enabled' => true,
            'disaster-intelligence.redaction.strict' => false,
        ]);

        Http::fake([
            'https://llm.test/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"waste_sites":[]}']],
                ],
            ], 200),
        ]);

        $parser = app(DisasterSourceParser::class);
        $parser->parse('waste_sites', implode("\n", [
            '仮置場: 市民体育館',
            '氏名：山田太郎',
            '連絡先: 090-1234-5678',
            'メール: taro@example.com',
            '受入期間: 11月1日から11月10日。',
        ]), [
            'source_org' => 'テスト県',
            'source_url' => 'https://www.pref.chiba.lg.jp/test.pdf',
            'source_published_at' => null,
            'scraped_at' => null,
            'raw_file_path' => null,
            'source_credibility' => 'A',
            'source_credibility_reason' => 'テスト',
        ]);

        Http::assertSent(function ($request) {
            $payload = json_decode($request->body(), true);
            $prompt = $payload['messages'][1]['content'] ?? '';

            return str_contains($prompt, '[REDACTED]')
                && !str_contains($prompt, '山田太郎')
                && !str_contains($prompt, '090-1234-5678')
                && !str_contains($prompt, 'taro@example.com');
        });
    }

    public function test_llm_prompt_keeps_road_information_unmasked(): void
    {
        config([
            'disaster-intelligence.llm.base_url' => 'https://llm.test/v1',
            'disaster-intelligence.llm.api_key' => 'test-key',
            'disaster-intelligence.llm.retries' => 1,
            'disaster-intelligence.redaction.enabled' => true,
            'disaster-intelligence.redaction.strict' => false,
        ]);

        Http::fake([
            'https://llm.test/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"roads":[]}']],
                ],
            ], 200),
        ]);

        $parser = app(DisasterSourceParser::class);
        $parser->parse('roads', implode("\n", [
            '道路名: 国道1号',
            '区間: 千葉市中央区から稲毛区',
            '担当: 山田太郎',
            '連絡先: 090-1234-5678',
            '全面通行止め。',
        ]), [
            'source_org' => 'テスト県',
            'source_url' => 'https://www.pref.chiba.lg.jp/test.pdf',
            'source_published_at' => null,
            'scraped_at' => null,
            'raw_file_path' => null,
            'source_credibility' => 'A',
            'source_credibility_reason' => 'テスト',
        ]);

        Http::assertSent(function ($request) {
            $payload = json_decode($request->body(), true);
            $prompt = $payload['messages'][1]['content'] ?? '';

            return !str_contains($prompt, '[REDACTED]')
                && str_contains($prompt, '国道1号')
                && str_contains($prompt, '山田太郎')
                && str_contains($prompt, '090-1234-5678');
        });
    }

    public function test_waste_site_without_geometry_is_kept(): void
    {
        config([
            'disaster-intelligence.llm.base_url' => 'https://llm.test/v1',
            'disaster-intelligence.llm.api_key' => 'test-key',
            'disaster-intelligence.llm.retries' => 1,
        ]);

        Http::fake([
            'https://llm.test/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'waste_sites' => [
                            [
                                'name' => '受入終了のお知らせ',
                                'status' => 'closed',
                                'geometry' => null,
                                'latitude' => null,
                                'longitude' => null,
                                'metadata' => ['notes' => '座標不明'],
                            ],
                        ],
                    ])]],
                ],
            ], 200),
        ]);

        $records = app(DisasterSourceParser::class)->parse('waste_sites', '一般受入は10月31日で終了しました。', [
            'source_org' => '氷見市',
            'source_url' => 'https://www.city.himi.toyama.jp/waste.html',
            'source_published_at' => null,
            'scraped_at' => null,
            'raw_file_path' => null,
            'source_credibility' => 'A',
            'source_credibility_reason' => '公式告知',
        ]);

        $this->assertCount(1, $records);
        $this->assertSame('受入終了のお知らせ', $records[0]['name']);
        $this->assertSame('closed', $records[0]['status']);
        $this->assertNull($records[0]['geometry']);
    }
}
