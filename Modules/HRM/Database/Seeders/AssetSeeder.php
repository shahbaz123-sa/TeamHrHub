<?php

namespace Modules\HRM\Database\Seeders;

use Modules\HRM\Models\Asset;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        Asset::truncate();
        Asset::factory()->createMany([
            ['name' => 'MacBook Pro 16"'],
            ['name' => 'Dell XPS 15'],
            ['name' => 'iPhone 14 Pro'],
            ['name' => 'Samsung Galaxy S23'],
            ['name' => 'Logitech MX Keys'],
            ['name' => 'Logitech MX Master 3'],
            ['name' => 'Dell 27" 4K Monitor'],
            ['name' => 'Jabra Evolve 75'],
            ['name' => 'Apple AirPods Pro'],
            ['name' => 'iPad Pro 12.9"']
        ]);
    }
}
