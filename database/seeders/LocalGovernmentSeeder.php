<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use \SplFileObject;
use Carbon\Carbon;


class LocalGovernmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $csv = new SplFileObject('database/seeders/seeder_import_csv/local_governments.csv');
        $csv->setFlags(
            \SplFileObject::READ_CSV |
            \SplFileObject::READ_AHEAD |
            \SplFileObject::SKIP_EMPTY |
            \SplFileObject::DROP_NEW_LINE
        );

        $designed_cities = [
            "大阪市",
            "名古屋市",
            "京都市",
            "横浜市",
            "神戸市",
            "北九州市",
            "札幌市",
            "川崎市",
            "福岡市",
            "広島市",
            "仙台市",
            "千葉市",
            "さいたま市",
            "静岡市",
            "堺市",
            "新潟市",
            "浜松市",
            "岡山市",
            "相模原市",
            "熊本市",
        ];

        $_rows = [];
        $header = [];
        $now = Carbon::now();

        foreach($csv as $line) {

            if (!in_array('id',$header)) {
                $header = $line;
                continue;
            }

            $is_designated = in_array($line[5], $designed_cities);

            $_rows[] = [
                $header[1] => (int)$line[1],
                $header[2] => (int)$line[2],
                $header[3] => $line[3],
                $header[4] => $line[4],
                $header[5] => $line[5],
                $header[6] => (int)$line[6],
                "is_designated_city" => $is_designated,
                "created_at" => $now,
                "updated_at" => $now,
            ];
        }

        DB::table("local_governments")->insert($_rows);

    }
}
