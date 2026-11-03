<?php

namespace App\Console\Commands;

use App\VolunteerCenter;
use Illuminate\Console\Command;

class VolunteerCentersContext extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'volunteer-centers:context';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = 'ボランティア募集情報の更新に必要なセンター情報をJSONで出力する';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $centers = VolunteerCenter::with('localGovernment.prefecture')
            ->orderBy('local_government_id')
            ->orderBy('name')
            ->get()
            ->map(function (VolunteerCenter $center) {
                return [
                    'id' => $center->id,
                    'name' => $center->name,
                    'municipality' => $center->localGovernment->name ?? '',
                    'prefecture' => $center->localGovernment->prefecture->name ?? '',
                    'prefecture_code' => $center->localGovernment->prefecture_code ?? '',
                    'address' => $center->address,
                    'phone' => $center->phone,
                    'url' => $center->url,
                    'latest_recruitment' => [
                        'title' => $center->latest_recruitment_title,
                        'summary' => $center->latest_recruitment_summary,
                        'url' => $center->latest_recruitment_url,
                        'updated_at' => $center->recruitment_updated_at?->toIso8601String(),
                    ],
                ];
            })
            ->values();

        $this->line(json_encode(
            $centers,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        ));

        return self::SUCCESS;
    }
}
