<?php

namespace App\Console\Commands;

use App\VolunteerCenter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VolunteerCentersIngest extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'volunteer-centers:ingest
                            {center : ボランティアセンターID}
                            {--file= : 募集情報JSONを読み込むファイル}
                            {--json= : 募集情報JSONを直接指定する}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = 'AIが取得したボランティア募集情報をセンターに登録する';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
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

        if ($title === '' && $summary === '') {
            $this->error('募集情報が空です。');
            return self::FAILURE;
        }

        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 255);
        }

        if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || mb_strlen($url) > 2048)) {
            $url = '';
        }

        $center->latest_recruitment_title = $title !== '' ? $title : null;
        $center->latest_recruitment_summary = $summary !== '' ? $summary : null;
        $center->latest_recruitment_url = $url !== '' ? $url : null;
        $center->recruitment_updated_at = now();

        if ($url !== '') {
            $center->source_url = $url;
            $center->source_name = 'ボランティアセンター公式サイト等';
        }

        $center->fetched_at = now();
        $center->save();

        $this->info('ボランティア募集情報を更新しました。');

        return self::SUCCESS;
    }
}
