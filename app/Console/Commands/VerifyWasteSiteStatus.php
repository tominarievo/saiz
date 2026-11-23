<?php

namespace App\Console\Commands;

use App\Disaster;
use App\Services\DisasterIntelligence\WasteSiteStatusVerifier;
use App\WasteSite;
use Illuminate\Console\Command;

class VerifyWasteSiteStatus extends Command
{
    /**
     * @var string
     */
    protected $signature = 'disaster-intelligence:verify-waste-sites
                            {disaster? : 災害ID（省略時は全災害）}
                            {--force : 判定結果を仮置場に反映する。省略時はdry-run}
                            {--fresh : 前回のAI判定結果を再利用せず、AIで再判定する}';

    /**
     * @var string
     */
    protected $description = '最新の受入状況と照合し、仮置場の状態を更新する';

    public function handle(WasteSiteStatusVerifier $verifier): int
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
            $sites = WasteSite::where('disaster_id', $disaster->id)
                ->orderBy('id')
                ->get()
                ->values();

            if ($sites->isEmpty()) {
                continue;
            }

            $records = $sites->map(function (WasteSite $site) {
                return [
                    'name' => $site->name,
                    'status' => $site->status,
                    'acceptance_end_at' => $site->acceptance_end_at?->toIso8601String(),
                    'accepted_items' => $site->accepted_items ?? [],
                ];
            })->all();

            try {
                $results = $verifier->verify($disaster->id, $records, (bool) $this->option('fresh'));
            } catch (\Throwable $error) {
                $errors++;
                $this->error("災害ID {$disaster->id}: 受入状況の確認に失敗しました: " . $error->getMessage());
                continue;
            }

            if ($results === []) {
                $this->line("災害ID {$disaster->id}: 判定できる受入情報がありませんでした。");
                continue;
            }

            $counts = ['open' => 0, 'closed' => 0, 'unknown' => 0];

            foreach ($results as $result) {
                if (isset($counts[$result['status']])) {
                    $counts[$result['status']]++;
                }
            }

            $summary = sprintf(
                'open %d件 / closed %d件 / unknown %d件',
                $counts['open'],
                $counts['closed'],
                $counts['unknown']
            );

            if (!(bool) $this->option('force')) {
                $this->line("災害ID {$disaster->id}: {$summary}（dry-run）");

                foreach ($results as $index => $result) {
                    if (!isset($sites[$index]) || !in_array($result['status'], ['closed', 'unknown'], true)) {
                        continue;
                    }

                    $site = $sites[$index];
                    $this->line(sprintf(
                        '  %s: #%d %s / %s',
                        strtoupper($result['status']),
                        $site->id,
                        $site->name,
                        $result['reason'] ?? '-'
                    ));
                }

                continue;
            }

            $updated = 0;

            foreach ($results as $index => $result) {
                if (!isset($sites[$index])) {
                    continue;
                }

                $site = $sites[$index];
                $site->status = $result['status'];
                $site->last_verified_at = now();
                $site->status_source_url = $result['source_url'];
                $site->status_evidence = $result['evidence'] ?? $result['reason'];

                if ($result['status'] === 'closed') {
                    $site->closed_at = $site->closed_at ?? now();
                } elseif ($result['status'] === 'open') {
                    $site->closed_at = null;
                }

                $site->save();
                $updated++;
            }

            $this->info("災害ID {$disaster->id}: {$summary}。{$updated}件を更新しました。");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
