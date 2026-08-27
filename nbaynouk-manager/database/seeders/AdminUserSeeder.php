<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && blank(env('ADMIN_PASSWORD'))) {
            throw new \RuntimeException('ADMIN_PASSWORD doit être défini explicitement en production.');
        }

        User::updateOrCreate(['email' => env('ADMIN_EMAIL', 'admin@nbaynouk.test')], [
            'name' => env('ADMIN_NAME', 'Redouane'),
            'password' => env('ADMIN_PASSWORD', app()->environment('production') ? null : 'password'),
        ]);
    }
}
