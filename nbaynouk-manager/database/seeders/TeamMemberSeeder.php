<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use Illuminate\Database\Seeder;

class TeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            ['name' => 'Redouane', 'default_role' => 'Chef de projet'],
            ['name' => 'Hajar', 'default_role' => 'Media Buyer'],
            ['name' => 'Youness', 'default_role' => 'Designer'],
            ['name' => 'Hamza', 'default_role' => 'Directeur créatif'],
            ['name' => 'Souda', 'default_role' => 'Filmmaker'],
        ];

        foreach ($members as $member) {
            TeamMember::query()->updateOrCreate(['name' => $member['name']], $member + ['active' => true]);
        }
    }
}
