<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\HRM\Models\Asset;
use Modules\HRM\Models\EmployeeAsset;
use Modules\HRM\Models\AssetAssignmentHistory;

class AssetAssignmentHistorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // If you want this to be re-runnable, clear only histories that match current pivot pairs.
            // Comment this out if you don't want any destructive behavior.
            // AssetAssignmentHistory::truncate();

            $now = now();

            // Build current assignments from the existing pivot.
            // employee_asset contains at most one row per asset (enforced by app logic), but we don't assume it.
            $pivotRows = EmployeeAsset::query()
                ->select(['asset_id', 'employee_id', 'assigned_date', 'created_at'])
                ->orderBy('asset_id')
                ->get();

            foreach ($pivotRows as $row) {
                $exists = AssetAssignmentHistory::query()
                    ->where('asset_id', $row->asset_id)
                    ->where('employee_id', $row->employee_id)
                    ->whereDate('assigned_date', $row->assigned_date)
                    ->exists();

                if ($exists) {
                    continue;
                }

                AssetAssignmentHistory::create([
                    'asset_id' => $row->asset_id,
                    'employee_id' => $row->employee_id,
                    'assigned_date' => $row->assigned_date,
                    // Still assigned -> keep returned_at null
                    'returned_at' => null,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->created_at ?? $now,
                ]);

                // Optional: ensure assets.assign_to matches pivot
                Asset::where('id', $row->asset_id)->update(['assign_to' => $row->employee_id]);
            }
        });
    }
}

