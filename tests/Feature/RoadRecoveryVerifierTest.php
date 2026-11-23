<?php

namespace Tests\Feature;

use App\Services\DisasterIntelligence\RoadRecoveryVerifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoadRecoveryVerifierTest extends TestCase
{
    private string $page = '';

    private ?string $llmStatus = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'disaster-intelligence.recovery_sources.101' => [
                [
                    'name' => 'テスト復旧情報',
                    'url' => 'https://www.pref.chiba.lg.jp/recovery.html',
                    'source_org' => '千葉県',
                ],
            ],
            'disaster-intelligence.crawler.enforce_allowlist' => true,
            'disaster-intelligence.crawler.resolve_dns' => false,
            'disaster-intelligence.crawler.respect_robots' => false,
            'disaster-intelligence.llm.base_url' => 'https://llm.test/v1',
            'disaster-intelligence.llm.api_key' => 'test-key',
            'disaster-intelligence.llm.retries' => 1,
            'disaster-intelligence.redaction.enabled' => true,
        ]);
    }

    public function test_ai_status_and_source_are_normalized(): void
    {
        Http::fake([
            'https://www.pref.chiba.lg.jp/recovery.html' => Http::response(
                '<html><body>国道1号 千葉市中央区 冠水 現在の状況：規制終了</body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://llm.test/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'roads' => [
                            [
                                'index' => 0,
                                'status' => 'open',
                                'reason' => '通行止め解除',
                                'evidence' => '国道1号の通行止めを解除しました。',
                                'source' => 'S1',
                            ],
                        ],
                    ])]],
                ],
            ], 200),
        ]);

        $results = app(RoadRecoveryVerifier::class)->verify(101, [
            [
                'road_name' => '国道1号',
                'section' => '千葉市',
                'reason' => '冠水',
                'regulation_type' => 'full_closure',
            ],
        ]);

        $this->assertSame('open', $results[0]['status']);
        $this->assertSame('https://www.pref.chiba.lg.jp/recovery.html', $results[0]['source_url']);
        $this->assertSame('国道1号の通行止めを解除しました。', $results[0]['evidence']);
        $this->assertSame(1, $this->llmCalls());
    }

    public function test_decisive_release_is_resolved_without_ai(): void
    {
        $this->fakeSources('国道1号の通行止めを解除しました。');

        $results = $this->verifyRoad();

        $this->assertSame('open', $results[0]['status']);
        $this->assertSame('https://www.pref.chiba.lg.jp/recovery.html', $results[0]['source_url']);
        $this->assertStringContainsString('解除しました', $results[0]['evidence']);
        $this->assertSame(0, $this->llmCalls());
    }

    public function test_fullwidth_road_name_matches(): void
    {
        $this->fakeSources('国道１号の通行止めを解除しました。');

        $results = $this->verifyRoad();

        $this->assertSame('open', $results[0]['status']);
        $this->assertSame(0, $this->llmCalls());
    }

    public function test_planned_release_is_sent_to_ai(): void
    {
        $this->fakeSources('国道1号の通行止め解除の見込みは未定です。', 'closed');

        $results = $this->verifyRoad();

        $this->assertSame('closed', $results[0]['status']);
        $this->assertSame(1, $this->llmCalls());
    }

    public function test_release_of_another_road_on_same_line_is_not_decisive(): void
    {
        $this->fakeSources('国道1号 通行止め継続 国道2号 通行止め解除', 'closed');

        $results = $this->verifyRoad();

        $this->assertSame('closed', $results[0]['status']);
        $this->assertSame(1, $this->llmCalls());
    }

    public function test_road_not_mentioned_is_unknown_without_ai(): void
    {
        $this->fakeSources('県道99号の通行止めを解除しました。');

        $results = $this->verifyRoad();

        $this->assertSame('unknown', $results[0]['status']);
        $this->assertSame(0, $this->llmCalls());
    }

    public function test_resolved_road_is_skipped(): void
    {
        $this->fakeSources('国道1号 通行止め継続中', 'closed');

        $results = $this->verifyRoad(['status' => 'open']);

        $this->assertSame([], $results);
        $this->assertSame(0, $this->llmCalls());
    }

    public function test_unchanged_excerpt_reuses_previous_ai_result(): void
    {
        $this->fakeSources('国道1号 通行止め継続中', 'closed');

        $first = $this->verifyRoad();
        $second = $this->verifyRoad();

        $this->assertSame('closed', $second[0]['status']);
        $this->assertSame($first, $second);
        $this->assertSame(1, $this->llmCalls());
    }

    public function test_changed_excerpt_is_sent_to_ai_again(): void
    {
        $this->fakeSources('国道1号 通行止め継続中', 'closed');

        $this->verifyRoad();
        $this->page = '国道1号 規制状況を更新（11月19日）';
        $this->verifyRoad();

        $this->assertSame(2, $this->llmCalls());
    }

    public function test_changes_outside_excerpt_do_not_trigger_ai(): void
    {
        $padding = str_repeat('お知らせ本文。', 100);
        $this->fakeSources('更新日 11月18日' . $padding . '国道1号 通行止め継続中' . $padding, 'closed');

        $this->verifyRoad();
        $this->page = '更新日 11月19日' . $padding . '国道1号 通行止め継続中' . $padding . '県道5号 新たに通行止め';
        $this->verifyRoad();

        $this->assertSame(1, $this->llmCalls());
    }

    public function test_fresh_option_ignores_previous_result(): void
    {
        $this->fakeSources('国道1号 通行止め継続中', 'closed');

        $this->verifyRoad();
        $this->verifyRoad([], true);

        $this->assertSame(2, $this->llmCalls());
    }

    public function test_ai_failure_is_not_cached(): void
    {
        $this->fakeSources('国道1号 通行止め継続中', null);

        $this->assertSame([], $this->verifyRoad());

        $this->llmStatus = 'closed';
        $results = $this->verifyRoad();

        $this->assertSame('closed', $results[0]['status']);
        $this->assertSame(2, $this->llmCalls());
    }

    public function test_only_excerpt_is_sent_to_ai(): void
    {
        $filler = str_repeat('関係のない長いお知らせです。', 1000);
        $this->fakeSources($filler . '国道1号 通行止め継続中' . $filler, 'closed');

        $this->verifyRoad();

        $request = Http::recorded(fn ($request) => str_contains($request->url(), 'llm.test'))->first()[0];
        $content = $request->data()['messages'][1]['content'];

        $this->assertStringContainsString('国道1号通行止め継続中', $content);
        $this->assertLessThan(3000, mb_strlen($content));
    }

    /**
     * 情報源とAIの応答を設定する。$this->page / $this->llmStatus を変えると次の取得に反映される。
     *
     * @param string $page 情報源の本文
     * @param string|null $llmStatus AIが返すstatus。nullの場合はAIを失敗させる
     */
    private function fakeSources(string $page, ?string $llmStatus = null): void
    {
        $this->page = $page;
        $this->llmStatus = $llmStatus;

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'llm.test')) {
                if ($this->llmStatus === null) {
                    return Http::response('', 500);
                }

                return Http::response([
                    'choices' => [
                        ['message' => ['content' => json_encode([
                            'roads' => [[
                                'index' => 0,
                                'status' => $this->llmStatus,
                                'reason' => 'AI判定',
                                'evidence' => 'AI根拠',
                                'source' => 'S1',
                            ]],
                        ])]],
                    ],
                ], 200);
            }

            return Http::response('<html><body>' . $this->page . '</body></html>', 200, ['Content-Type' => 'text/html']);
        });
    }

    private function verifyRoad(array $overrides = [], bool $fresh = false): array
    {
        return app(RoadRecoveryVerifier::class)->verify(101, [array_merge([
            'road_name' => '国道1号',
            'section' => '千葉市',
            'reason' => '冠水',
            'regulation_type' => 'full_closure',
            'status' => 'closed',
        ], $overrides)], $fresh);
    }

    private function llmCalls(): int
    {
        return Http::recorded(fn ($request) => str_contains($request->url(), 'llm.test'))->count();
    }

    public function test_keyword_fallback_runs_when_llm_fails(): void
    {
        Http::fake([
            'https://www.pref.chiba.lg.jp/recovery.html' => Http::response(
                '<html><body>県道2号は通行止めを解除しました。</body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://llm.test/v1/chat/completions' => Http::response('', 500),
        ]);

        $results = app(RoadRecoveryVerifier::class)->verify(101, [
            [
                'road_name' => '県道2号',
                'section' => '中央区',
                'reason' => '冠水',
                'regulation_type' => 'alternating',
            ],
        ]);

        $this->assertSame('open', $results[0]['status']);
        $this->assertSame('https://www.pref.chiba.lg.jp/recovery.html', $results[0]['source_url']);
    }

    public function test_keyword_fallback_marks_non_full_closure_open(): void
    {
        Http::fake([
            'https://www.pref.chiba.lg.jp/recovery.html' => Http::response(
                '<html><body>県道2号は片側交互通行を解除し、全面開通しました。</body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://llm.test/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"roads":[]}']],
                ],
            ], 200),
        ]);

        $results = app(RoadRecoveryVerifier::class)->verify(101, [
            [
                'road_name' => '県道2号',
                'section' => '中央区',
                'reason' => '冠水',
                'regulation_type' => 'alternating',
            ],
        ]);

        $this->assertSame('open', $results[0]['status']);
        $this->assertSame('https://www.pref.chiba.lg.jp/recovery.html', $results[0]['source_url']);
        $this->assertStringContainsString('全面開通', $results[0]['evidence']);
    }
}
