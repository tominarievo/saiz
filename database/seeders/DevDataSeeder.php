<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\User::firstOrCreate(
            [
                'username' => 'ando2@d-cradle.or.jp',
            ],
            [
                'is_valid'            => true,
                'password'            => Hash::make('Test0001'),
                'name'                => '管理者',
                'organization_id'     => null,
                'email'               => 'ando2@d-cradle.or.jp'
            ]
        );
    }
}
