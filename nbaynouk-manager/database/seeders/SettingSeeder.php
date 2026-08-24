<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(['key' => 'agency_name'], ['value' => 'Nbaynouk']);
        Setting::updateOrCreate(['key' => 'currency'], ['value' => 'MAD']);
    }
}
