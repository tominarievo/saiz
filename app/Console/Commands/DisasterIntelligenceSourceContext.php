<?php

namespace App\Console\Commands;

use App\Disaster;
use App\DisasterIntelligenceSource;
use Illuminate\Console\Command;

class DisasterIntelligenceSourceContext extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'disaster-intelligence:source-context {disaster : 災害ID}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = 'AIによる収集元探索に必要な災害情報と既存収集元をJSONで出力する';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $disaster = Disaster::with('localGovernments.prefecture')->find($this->argument('disaster'));

        if (!$disaster) {
            $this->error('災害が見つかりません。');
            return self::FAILURE;
        }

        $configured = collect(config('disaster-intelligence.sources.' . $disaster->id, []))
            ->map(function ($source) {
                return [
                    'type' => $source['type'] ?? '',
                    'url' => $source['url'] ?? '',
                    'source_org' => $source['source_org'] ?? '',
                    'published_at' => $source['published_at'] ?? null,
                    'credibility_rank' => $source['credibility_rank'] ?? null,
                ];
            });

        $discovered = DisasterIntelligenceSource::where('disaster_id', $disaster->id)
            ->where('status', 'active')
            ->get()
            ->map(function (DisasterIntelligenceSource $source) {
                return [
                    'type' => $source->type,
                    'url' => $source->url,
                    'source_org' => $source->source_org,
                    'published_at' => $source->published_at?->toIso8601String(),
                    'credibility_rank' => $source->credibility_rank,
                ];
            });

        $sources = $configured->merge($discovered)
            ->keyBy(fn ($source) => ($source['type'] ?? '') . '|' . ($source['url'] ?? ''))
            ->values();

        $municipalities = $disaster->localGovernments
            ->map(fn ($localGovernment) => trim(($localGovernment->prefecture->name ?? '') . ' ' . $localGovernment->name))
            ->values();

        $this->line(json_encode([
            'disaster_id' => $disaster->id,
            'name' => $disaster->name,
            'event_date' => $disaster->event_date,
            'municipalities' => $municipalities,
            'sources' => $sources,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
