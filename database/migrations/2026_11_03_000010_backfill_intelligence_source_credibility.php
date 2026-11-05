<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['road_regulations', 'waste_sites', 'meeting_insights'] as $tableName) {
            DB::table($tableName)
                ->whereNull('source_credibility')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($tableName) {
                    foreach ($rows as $row) {
                        DB::table($tableName)
                            ->where('id', $row->id)
                            ->update([
                                'source_credibility' => $this->rank((string) $row->source_url),
                                'source_credibility_reason' => 'URLドメインから判定',
                            ]);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['road_regulations', 'waste_sites', 'meeting_insights'] as $tableName) {
            DB::table($tableName)->update([
                'source_credibility' => null,
                'source_credibility_reason' => null,
            ]);
        }
    }

    private function rank(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        if (str_ends_with($host, '.go.jp') || str_ends_with($host, '.lg.jp')) {
            return 'A';
        }

        if (preg_match('/^(city|town|vill|pref)\./', $host) && str_ends_with($host, '.jp')) {
            return 'A';
        }

        if (str_ends_with($host, '.or.jp')) {
            return 'B';
        }

        return 'C';
    }
};
