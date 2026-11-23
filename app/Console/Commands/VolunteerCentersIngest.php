<?php

namespace App\Console\Commands;

use App\Disaster;
use App\Services\DisasterDataSource\DisasterDataSourceRecorder;
use App\Support\DisasterDataSourceCategory;
use App\VolunteerCenter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VolunteerCentersIngest extends Command
{
    /**
     * @var string
     */
    protected $signature = 'volunteer-centers:ingest
                            {center : ボランティアセンターID}
                            {--file= : 募集情報JSONを読み込むファイル}
                            {--json= : 募集情報JSONを直接指定する}';

    /**
     * @var string
     */
    protected $description = 'AIが取得したボランティア募集情報と開設状況をセンターに登録する';

    public function handle(): int
    {
        $center = VolunteerCenter::find($this->argument('center'));

        if (!$center) {
            $this->error('ボランティアセンターが見つかりません。');
            return self::FAILURE;
        }

        $json = $this->option('json');

        if ($json === null && $this->option('file')) {
            $path = $this->option('file');

            if (!File::exists($path)) {
                $this->error('指定されたファイルが見つかりません。');
                return self::FAILURE;
            }

            $json = File::get($path);
        }

        if ($json === null) {
            $json = stream_get_contents(STDIN);
        }

        $data = json_decode((string) $json, true);

        if (!is_array($data)) {
            $this->error('JSONの形式が正しくありません。');
            return self::FAILURE;
        }

        $title = trim((string) ($data['title'] ?? ''));
        $summary = trim((string) ($data['summary'] ?? ''));
        $url = trim((string) ($data['url'] ?? ''));
        $status = strtolower(trim((string) ($data['status'] ?? 'unknown')));
        $statusNote = trim((string) ($data['status_note'] ?? ''));

        if (!in_array($status, ['open', 'closed', 'unknown'], true)) {
            $status = 'unknown';
        }

        if ($statusNote !== '') {
            $statusNote = mb_substr($statusNote, 0, 1000);
        }

        if ($title === '' && $summary === '' && $status === 'unknown') {
            $this->error('募集情報が空です。');
            return self::FAILURE;
        }

        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 255);
        }

        if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || mb_strlen($url) > 2048)) {
            $url = '';
        }

        if ($status === 'closed') {
            $center->latest_recruitment_title = $title !== '' ? $title : '募集終了';
            $center->latest_recruitment_summary = $summary !== ''
                ? $summary
                : 'ボランティアセンターの閉所・活動終了が確認されました。';
        } else {
            $center->latest_recruitment_title = $title !== '' ? $title : null;
            $center->latest_recruitment_summary = $summary !== '' ? $summary : null;
        }

        $center->latest_recruitment_url = $url !== '' ? $url : null;
        $center->recruitment_updated_at = now();

        if ($status !== 'unknown') {
            $center->activity_status = $status;
            $center->status_note = $statusNote !== '' ? $statusNote : null;
            $center->status_source_name = $url !== ''
                ? 'ボランティアセンター公式サイト等'
                : $center->status_source_name;
            $center->status_source_url = $url !== '' ? $url : $center->status_source_url;
        } elseif ($statusNote !== '') {
            $center->status_note = $statusNote;
        }

        $center->status_checked_at = now();

        if ($url !== '') {
            $center->source_url = $url;
            $center->source_name = 'ボランティアセンター公式サイト等';
        }

        $center->fetched_at = now();
        $center->save();

        $disasterIds = Disaster::whereHas('localGovernments', function ($query) use ($center) {
            $query->where('local_governments.id', $center->local_government_id);
        })->pluck('id');

        $recorder = app(DisasterDataSourceRecorder::class);

        foreach ($disasterIds as $disasterId) {
            $recorder->record(
                (int) $disasterId,
                DisasterDataSourceCategory::VOLUNTEER_CENTERS,
                $center->source_name,
                $center->source_url,
                $center->name,
                $center->fetched_at,
                null,
                [
                    'center_id' => $center->id,
                    'activity_status' => $center->activity_status,
                    'status_checked_at' => $center->status_checked_at?->toIso8601String(),
                ]
            );
        }

        $this->info('ボランティア募集情報・開設状況を更新しました。');

        return self::SUCCESS;
    }
}
