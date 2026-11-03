<?php

namespace App\Console\Commands;

use App\Disaster;
use Illuminate\Console\Command;

class DisasterLinksContext extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'disaster-links:context
                            {--disaster= : 対象の災害ID}
                            {--all : 公開中以外の災害も含める}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = '最新情報の自動更新に必要な災害名・市町村・既存リンクをJSONで出力する';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $query = Disaster::with(['localGovernments.prefecture', 'informationLinks'])
            ->orderBy('event_date', 'DESC');

        if (! $this->option('all')) {
            $query->where('status', true);
        }

        if ($this->option('disaster')) {
            $query->whereKey($this->option('disaster'));
        }

        $disasters = $query->get()
            ->filter(function (Disaster $disaster) {
                return $disaster->localGovernments->isNotEmpty();
            })
            ->values()
            ->map(function (Disaster $disaster) {
                $municipalities = $disaster->localGovernments
                    ->map(function ($localGovernment) {
                        $prefectureName = $localGovernment->prefecture->name ?? '';

                        return trim($prefectureName . ' ' . $localGovernment->name);
                    })
                    ->filter()
                    ->unique()
                    ->values();

                $prefectures = $disaster->localGovernments
                    ->map(function ($localGovernment) {
                        return $localGovernment->prefecture->name ?? null;
                    })
                    ->filter()
                    ->unique()
                    ->values();

                $manualLinks = $disaster->informationLinks
                    ->where('source', 'manual')
                    ->map(function ($link) {
                        return [
                            'description' => $link->description,
                            'url' => $link->url,
                        ];
                    })
                    ->values();

                return [
                    'id' => $disaster->id,
                    'name' => $disaster->name,
                    'event_date' => $disaster->event_date,
                    'year' => $disaster->npo_col_2,
                    'prefectures' => $prefectures,
                    'municipalities' => $municipalities,
                    'manual_links' => $manualLinks,
                ];
            });

        $this->line(json_encode(
            $disasters,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        ));

        return self::SUCCESS;
    }
}
