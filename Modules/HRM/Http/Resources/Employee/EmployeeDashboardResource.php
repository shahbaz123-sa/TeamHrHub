<?php

namespace Modules\HRM\Http\Resources\Employee;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDashboardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'attendance_stats' => [
                'total_early_check_in' => $this->resource['attendance_stats']['total_early_check_in'] ?? 0,
                'total_late_check_in' => $this->resource['attendance_stats']['total_late_check_in'] ?? 0,
                'total_early_check_out' => $this->resource['attendance_stats']['total_early_check_out'] ?? 0,
                'total_late_check_out' => $this->resource['attendance_stats']['total_late_check_out'] ?? 0,
                'total_presents' => $this->resource['attendance_stats']['total_presents'] ?? 0,
                'total_leaves' => $this->resource['attendance_stats']['total_leaves'] ?? 0,
                'total_absent' => $this->resource['attendance_stats']['total_absent'] ?? 0,
                'leave_breakdown' => $this->resource['attendance_stats']['leave_breakdown'] ?? [
                    'full_day_leaves' => 0,
                    'half_day_leaves' => 0,
                    'short_leaves' => 0,
                ],
                'total_records' => $this->resource['attendance_stats']['total_records'] ?? 0,
            ],
            'can_checkin' => $this->resource['can_checkin'] ?? false,
            'period_info' => [
                'month' => $this->resource['period_info']['month'] ?? null,
                'year' => $this->resource['period_info']['year'] ?? null,
                'month_name' => $this->resource['period_info']['month_name'] ?? null,
                'total_days' => $this->resource['period_info']['total_days'] ?? null,
            ],
            'meta' => [
                'generated_at' => now()->toISOString(),
                'timezone' => config('app.timezone'),
            ]
        ];
    }
}
