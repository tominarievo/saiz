<?php

namespace App\Console\Commands;

use App\Disaster;
use App\RoadRegulation;
use App\Services\DisasterIntelligence\RoadRecoveryVerifier;
use Illuminate\Console\Command;

class VerifyRoadRecovery extends Command
{
    /**
     * @var string
     */
    protected $signature = 'disaster-intelligence:verify-roads
                            {disaster? : 災害ID（省略時は全災害）}
                            {--force : 判定結果を道路規制に反映する。省略時はdry-run}
                            {--fresh : 前回のAI判定結果を再利用せず、AIで再判定する}';

    /**
     * @var string
     */
    protected $description = '最新の復旧情報と照合し、道路規制の状態を更新する';

    /**
     * Execute the console command.
     */
    public function handle(RoadRecoveryVerifier $verifier): int
    {
        $disasterId = $this->argument('disaster');
        $disasters = $disasterId
            ? Disaster::where('id', (int) $disasterId)->get()
            : Disaster::all();

        if ($disasters->isEmpty()) {
            $this->error('対象の災害が見つかりません。');

            return self::FAILURE;
        }

        $errors = 0;

        foreach ($disasters as $disaster) {
            $sources = config('disaster-intelligence.recovery_sources.' . $disaster->id, []);

            if (!is_array($sources) || $sources === []) {
                $this->warn("災害ID {$disaster->id}: 復旧確認用の情報源が未設定です。");
                continue;
            }

            $roads = RoadRegulation::where('disaster_id', $disaster->id)
                ->orderBy('id')
                ->get()
                ->values();

            if ($roads->isEmpty()) {
                continue;
            }

            $records = $roads->map(function (RoadRegulation $road) {
                return [
                    'road_name' => $road->road_name,
                    'section' => $road->section,
                    'reason' => $road->reason,
                    'regulation_type' => $road->regulation_type,
                    'status' => $road->status,
                ];
            })->all();

            try {
                $results = $verifier->verify($disaster->id, $records, (bool) $this->option('fresh'));
            } catch (\Throwable $error) {
                $errors++;
                $this->error("災害ID {$disaster->id}: 復旧確認に失敗しました: " . $error->getMessage());
                continue;
            }

            if ($results === []) {
                $this->line("災害ID {$disaster->id}: 判定できる復旧情報がありませんでした。");
                continue;
            }

            $counts = ['open' => 0, 'partial' => 0, 'closed' => 0, 'unknown' => 0];

            foreach ($results as $result) {
                if (isset($counts[$result['status']])) {
                    $counts[$result['status']]++;
                }
            }

            $summary = sprintf(
                'open %d件 / partial %d件 / closed %d件 / unknown %d件',
                $counts['open'],
                $counts['partial'],
                $counts['closed'],
                $counts['unknown']
            );

            if ($counts['unknown'] > 0) {
                $this->warn("要確認: unknown {$counts['unknown']}件。復旧情報源の追加を検討してください。");
            }

            if (!(bool) $this->option('force')) {
                $this->line("災害ID {$disaster->id}: {$summary}（dry-run）");

                foreach ($results as $index => $result) {
                    if (!isset($roads[$index]) || !in_array($result['status'], ['open', 'unknown'], true)) {
                        continue;
                    }

                    $road = $roads[$index];
                    $this->line(sprintf(
                        '  %s: #%d %s / %s / %s',
                        strtoupper($result['status']),
                        $road->id,
                        $road->road_name,
                        $road->section ?: '-',
                        $result['reason'] ?? '-'
                    ));
                }

                continue;
            }

            $updated = 0;

            foreach ($results as $index => $result) {
                if (!isset($roads[$index])) {
                    continue;
                }

                $road = $roads[$index];
                $road->status = $result['status'];
                $road->last_verified_at = now();
                $road->recovery_source_url = $result['source_url'];
                $road->recovery_evidence = $result['evidence'] ?? $result['reason'];

                if ($result['status'] === 'open') {
                    $road->recovered_at = $road->recovered_at ?? now();
                } elseif ($result['status'] === 'closed') {
                    $road->recovered_at = null;
                }

                $road->save();
                $updated++;
            }

            $this->info("災害ID {$disaster->id}: {$summary}。{$updated}件を更新しました。");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
