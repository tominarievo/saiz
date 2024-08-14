<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use \SplFileObject;
use Carbon\Carbon;


class PrefectureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $csv = new SplFileObject('database/seeders/seeder_import_csv/prefectures.csv');
        $csv->setFlags(
            \SplFileObject::READ_CSV |
            \SplFileObject::READ_AHEAD |
            \SplFileObject::SKIP_EMPTY |
            \SplFileObject::DROP_NEW_LINE
        );

        $_rows = [];
        $header = [];
        $now = Carbon::now();

        foreach($csv as $line) {

            if (!in_array('id',$header)) {
                $header = $line;
                continue;
            }

            $_rows[] = [
                $header[1] => $line[1],
                $header[2] => (int)$line[2],
                $header[3] => $line[3],
                "created_at" => $now,
                "updated_at" => $now,
            ];
        }

        DB::table("prefectures")->insert($_rows);

    }
}
