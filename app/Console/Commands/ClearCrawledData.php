<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ClearCrawledData extends Command
{
    /**
     * @var string
     */
    protected $signature = 'crawled-data:clear
                            {--dry-run : 削除せず対象件数のみ表示する}
                            {--force : 実際に削除する}
                            {--keep-jobs : 解析待ちのParseDisasterSourceJobを残す}';

    /**
     * @var string
     */
    protected $description = 'cronで収集したデータ（AI抽出・最新情報・避難所状況・ボランティア募集・生ファイル）を削除する';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run') || !(bool) $this->option('force');
        $deleteJobs = !(bool) $this->option('keep-jobs');

        $diskName = (string) config('disaster-intelligence.storage_disk', 'local');
        $root = trim((string) config('disaster-intelligence.source_root', 'disaster-sources'), '/');
        $disk = Storage::disk($diskName);

        $counts = [
            '道路規制' => $this->tableCount('road_regulations'),
            '災害廃棄物・仮置場' => $this->tableCount('waste_sites'),
            '議事録' => $this->tableCount('meeting_insights'),
            'AI収集元' => $this->tableCount('disaster_intelligence_sources'),
            '出典メタデータ' => $this->tableCount('disaster_data_sources'),
            'AI最新情報リンク' => $this->aiInformationLinkCount(),
            '避難所開設状況（リセット）' => $this->shelterStatusCount(),
            'ボランティア募集（リセット）' => $this->volunteerRecruitmentCount(),
            '解析待ちジョブ' => $deleteJobs ? $this->parseJobCount('jobs') : 0,
            '失敗した解析ジョブ' => $deleteJobs ? $this->parseJobCount('failed_jobs') : 0,
            '生ファイル' => count($disk->allFiles($root)),
        ];

        $this->table(['対象', '件数'], collect($counts)->map(
            fn (int $count, string $label) => [$label, $count]
        )->values()->all());

        if ($dryRun) {
            $this->info('dry-run のため削除していません。実行する場合は --force を指定してください。');

            return self::SUCCESS;
        }

        if ($counts['生ファイル'] > 0) {
            try {
                $disk->deleteDirectory($root);
                $disk->makeDirectory($root);
            } catch (Throwable $error) {
                $this->error('生ファイルの削除に失敗しました: ' . $error->getMessage());

                return self::FAILURE;
            }
        }

        DB::transaction(function () use ($deleteJobs) {
            DB::table('road_regulations')->delete();
            DB::table('waste_sites')->delete();
            DB::table('meeting_insights')->delete();
            DB::table('disaster_intelligence_sources')->delete();
            DB::table('disaster_data_sources')->delete();
            DB::table('disaster_information_links')->where('source', 'ai')->delete();

            DB::table('evacuation_shelters')->update([
                'is_active' => false,
                'activity_status' => 'unknown',
                'evacuees' => null,
                'status_note' => null,
                'status_source_name' => null,
                'status_source_url' => null,
                'status_checked_at' => null,
                'status_changed_at' => null,
            ]);

            DB::table('volunteer_centers')->update([
                'latest_recruitment_title' => null,
                'latest_recruitment_summary' => null,
                'latest_recruitment_url' => null,
                'recruitment_updated_at' => null,
                'fetched_at' => null,
            ]);

            if ($deleteJobs) {
                if (Schema::hasTable('jobs')) {
                    DB::table('jobs')
                        ->where('payload', 'like', '%ParseDisasterSourceJob%')
                        ->delete();
                }

                if (Schema::hasTable('failed_jobs')) {
                    DB::table('failed_jobs')
                        ->where('payload', 'like', '%ParseDisasterSourceJob%')
                        ->delete();
                }
            }
        });

        Cache::flush();

        $this->info('cronで収集したデータを削除しました。');

        return self::SUCCESS;
    }

    private function tableCount(string $table): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    private function aiInformationLinkCount(): int
    {
        if (!Schema::hasTable('disaster_information_links')) {
            return 0;
        }

        return (int) DB::table('disaster_information_links')->where('source', 'ai')->count();
    }

    private function shelterStatusCount(): int
    {
        if (!Schema::hasTable('evacuation_shelters')) {
            return 0;
        }

        return (int) DB::table('evacuation_shelters')
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('activity_status', '!=', 'unknown')
                    ->orWhereNotNull('status_checked_at')
                    ->orWhereNotNull('status_source_name');
            })
            ->count();
    }

    private function volunteerRecruitmentCount(): int
    {
        if (!Schema::hasTable('volunteer_centers')) {
            return 0;
        }

        return (int) DB::table('volunteer_centers')
            ->where(function ($query) {
                $query->whereNotNull('latest_recruitment_title')
                    ->orWhereNotNull('latest_recruitment_summary')
                    ->orWhereNotNull('latest_recruitment_url')
                    ->orWhereNotNull('recruitment_updated_at')
                    ->orWhereNotNull('fetched_at');
            })
            ->count();
    }

    private function parseJobCount(string $table): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)
            ->where('payload', 'like', '%ParseDisasterSourceJob%')
            ->count();
    }
}
