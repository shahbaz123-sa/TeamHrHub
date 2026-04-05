<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\Holiday;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = [
            [
                'name' => 'New Year\'s Day',
                'date' => '2025-01-01',
                'is_recurring' => true,
                'description' => 'Celebration of the new year',
            ],
            [
                'name' => 'Independence Day',
                'date' => '2025-08-14',
                'is_recurring' => true,
                'description' => 'Pakistan Independence Day',
            ],
            [
                'name' => 'Eid al-Fitr',
                'date' => '2025-03-30',
                'is_recurring' => false,
                'description' => 'End of Ramadan celebration',
            ],
            [
                'name' => 'Eid al-Adha',
                'date' => '2025-06-06',
                'is_recurring' => false,
                'description' => 'Festival of Sacrifice',
            ],
            [
                'name' => 'Christmas Day',
                'date' => '2025-12-25',
                'is_recurring' => true,
                'description' => 'Christian celebration of Christmas',
            ],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }
    }
}

