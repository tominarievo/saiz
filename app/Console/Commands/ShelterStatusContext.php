<?php

namespace App\Console\Commands;

use App\EvacuationShelter;
use App\LocalGovernment;
use Illuminate\Console\Command;

class ShelterStatusContext extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'shelter-status:context {--admin-code= : 対象の5桁行政コード}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = '避難所の開設状況を収集するための市町村・候補避難所・情報源をJSONで出力する';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sources = config('shelter-status-sources', []);
        $only = $this->option('admin-code');
        $result = [];

        foreach ($sources as $adminCode => $config) {
            if ($only && (string) $adminCode !== (string) $only) {
                continue;
            }

            $localGovernments = LocalGovernment::with('prefecture')
                ->where('code', 'like', $adminCode . '%')
                ->get();

            if ($localGovernments->isEmpty()) {
                continue;
            }

            $localGovernmentIds = $localGovernments->pluck('id')->values();
            $shelters = EvacuationShelter::whereIn('local_government_id', $localGovernmentIds)
                ->where(function ($query) {
                    $query->where('shelter_type', 'designated_shelter')
                        ->orWhere('is_active', true);
                })
                ->orderBy('name')
                ->get()
                ->map(function (EvacuationShelter $shelter) {
                    return [
                        'id' => $shelter->id,
                        'name' => $shelter->name,
                        'address' => $shelter->address,
                        'shelter_type' => $shelter->shelter_type,
                        'is_active' => $shelter->is_active,
                    ];
                })
                ->values();

            if ($shelters->isEmpty()) {
                continue;
            }

            $first = $localGovernments->first();

            $result[] = [
                'admin_code' => (string) $adminCode,
                'collector' => $config['collector'] ?? 'shelter_ai',
                'municipality' => $config['name'] ?? $first->name,
                'prefecture' => $first->prefecture->name ?? '',
                'local_government_ids' => $localGovernmentIds,
                'sources' => array_values($config['sources'] ?? []),
                'active_count' => $shelters->where('is_active', true)->count(),
                'shelters' => $shelters,
            ];
        }

        $this->line(json_encode(
            $result,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        ));

        return self::SUCCESS;
    }
}
