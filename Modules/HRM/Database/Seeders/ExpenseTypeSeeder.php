<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\ExpenseType;

class ExpenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        ExpenseType::truncate();
        ExpenseType::factory()->createMany([
            ['name' => 'Travel'],
            ['name' => 'Meals'],
            ['name' => 'Entertainment'],
            ['name' => 'Office Supplies'],
            ['name' => 'Training']
        ]);
    }
}
