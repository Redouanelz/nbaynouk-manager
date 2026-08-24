<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Meta Ads', 'TikTok Ads', 'Stratégie de contenu', 'Production vidéo',
            'UGC', 'Photographie', 'Design graphique', 'Community Management',
            'Site web', 'Shopify', 'Développement Laravel', 'Copywriting', 'Consulting',
        ];

        foreach ($names as $name) {
            Service::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
