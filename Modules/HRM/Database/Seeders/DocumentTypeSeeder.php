<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        DocumentType::truncate();
        DocumentType::factory()->createMany([
            ['name' => 'Passport'],
            ['name' => 'National ID'],
            ['name' => 'Degree Certificate'],
            ['name' => 'Employment Contract'],
            ['name' => 'Tax Documents']
        ]);
    }
}
