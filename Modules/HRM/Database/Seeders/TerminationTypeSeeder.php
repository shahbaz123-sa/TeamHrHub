<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\TerminationType;

class TerminationTypeSeeder extends Seeder
{
    public function run(): void
    {
        TerminationType::truncate();
        TerminationType::factory()->createMany([
            ['name' => 'Resignation'],
            ['name' => 'Termination'],
            ['name' => 'Retirement'],
            ['name' => 'End of Contract']
        ]);
    }
}
