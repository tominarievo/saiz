<?php

namespace Database\Seeders;

use App\DisasterType;
use Illuminate\Database\Seeder;

class DisasterTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $list = [
            '1'  => '暴風',
            '2'  => '竜巻',
            '3'  => '豪雨',
            '4'  => '豪雪',
            '5'  => '洪水',
            '6'  => '崖崩れ、土石流、地滑り',
            '7'  => '高潮',
            '8'  => '地震',


            '9'  => '津波',
            '10'  => '噴火',
            '11'  => '大規模な火事',
            '12'  => '爆発',
            '13'  => 'その他',
        ];


        foreach ($list as $code => $name) {
            $disaster_type = DisasterType::where('code', $code)->first();

            if ( ! $disaster_type) {
                $disaster_type = new DisasterType();
                $disaster_type->code = $code;
            }

            $disaster_type->order = $code;
            $disaster_type->name  = $name;
            $disaster_type->save();
        }
    }
}
